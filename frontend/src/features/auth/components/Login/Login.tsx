import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/features/auth/context/AuthContext';
import styles from './Login.module.scss';

export const Login = () => {
  const navigate = useNavigate();
  const { requestLoginCode } = useAuth();

  const [phone, setPhone] = useState('');
  const [error, setError] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);

  const formatPhoneNumber = (value: string): string => {
    const digits = value.replace(/\D/g, '');

    if (digits.length === 0) return '';
    if (digits.length <= 1) return `+7`;
    if (digits.length <= 4) return `+7 (${digits.slice(1)}`;
    if (digits.length <= 7) return `+7 (${digits.slice(1, 4)}) ${digits.slice(4)}`;
    if (digits.length <= 9)
      return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7)}`;
    return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9, 11)}`;
  };

  const handlePhoneChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;
    const formatted = formatPhoneNumber(value);
    setPhone(formatted);
    setError('');
  };

  const getCleanPhone = (formatted: string): string => {
    return '+' + formatted.replace(/\D/g, '');
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    const cleanPhone = getCleanPhone(phone);

    if (!cleanPhone || cleanPhone.length !== 12) {
      setError('Введите корректный номер телефона');
      return;
    }

    setIsLoading(true);

    try {
      await requestLoginCode(cleanPhone);
      navigate('/verify-login', {
        state: {
          phone: cleanPhone,
          mode: 'login',
        },
      });
    } catch (err: any) {
      console.error('Request login code error:', err);
      if (err.response?.status === 404) {
        setError('Пользователь с таким номером не найден');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Ошибка при отправке кода. Попробуйте позже');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleRegisterClick = () => {
    navigate('/register');
  };

  return (
    <div className={styles.login}>
      <div className={styles.loginCard}>
        <div className={styles.header}>
          <h1 className={styles.title}>Вход</h1>
          <p className={styles.subtitle}>Войдите в свой аккаунт</p>
        </div>

        <form onSubmit={handleSubmit} className={styles.form}>
          <div className={styles.formGroup}>
            <label htmlFor="phone" className={styles.label}>
              Номер телефона
            </label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value={phone}
              onChange={handlePhoneChange}
              className={styles.input}
              placeholder="+7 (___) ___-__-__"
              disabled={isLoading}
              autoComplete="tel"
              autoFocus
            />
          </div>

          {error && <div className={styles.error}>{error}</div>}

          <button type="submit" className={styles.submitButton} disabled={isLoading}>
            {isLoading ? 'Отправка...' : 'Получить код'}
          </button>

          <p className={styles.hint}>Мы отправим вам SMS-код для входа</p>
        </form>

        <div className={styles.footer}>
          <p className={styles.footerText}>
            Нет аккаунта?{' '}
            <button
              type="button"
              onClick={handleRegisterClick}
              className={styles.linkButton}
              disabled={isLoading}
            >
              Зарегистрироваться
            </button>
          </p>
        </div>
      </div>
    </div>
  );
};
