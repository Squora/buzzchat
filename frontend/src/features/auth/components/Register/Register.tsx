import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '@/features/auth/context/AuthContext';
import styles from './Register.module.scss';

export const Register = () => {
  const navigate = useNavigate();
  const { register } = useAuth();

  const [formData, setFormData] = useState({
    phone: '',
    email: '',
    firstName: '',
    lastName: '',
  });
  const [error, setError] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));
    setError('');
  };

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
    setFormData((prev) => ({
      ...prev,
      phone: formatted,
    }));
    setError('');
  };

  const getCleanPhone = (formatted: string): string => {
    return '+' + formatted.replace(/\D/g, '');
  };

  const validateForm = (): boolean => {
    const cleanPhone = getCleanPhone(formData.phone);

    if (!cleanPhone || cleanPhone.length !== 12) {
      setError('Введите корректный номер телефона');
      return false;
    }

    if (!formData.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
      setError('Введите корректный email');
      return false;
    }

    if (!formData.firstName.trim()) {
      setError('Введите имя');
      return false;
    }

    if (!formData.lastName.trim()) {
      setError('Введите фамилию');
      return false;
    }

    return true;
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!validateForm()) {
      return;
    }

    setIsLoading(true);

    try {
      const cleanPhone = getCleanPhone(formData.phone);
      const response = await register({
        phone: cleanPhone,
        email: formData.email,
        firstName: formData.firstName,
        lastName: formData.lastName,
      });

      // Navigate to verification page with phone number
      navigate('/verify-register', {
        state: {
          phone: cleanPhone,
          mode: 'register',
        },
      });
    } catch (err: any) {
      console.error('Registration error:', err);
      if (err.response?.status === 409) {
        setError('Пользователь с таким номером или email уже существует');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Ошибка при регистрации. Попробуйте позже');
      }
    } finally {
      setIsLoading(false);
    }
  };

  const handleLoginClick = () => {
    navigate('/login');
  };

  return (
    <div className={styles.register}>
      <div className={styles.registerCard}>
        <div className={styles.header}>
          <h1 className={styles.title}>Регистрация</h1>
          <p className={styles.subtitle}>Создайте аккаунт в BuzzChat</p>
        </div>

        <form onSubmit={handleSubmit} className={styles.form}>
          <div className={styles.formRow}>
            <div className={styles.formGroup}>
              <label htmlFor="firstName" className={styles.label}>
                Имя
              </label>
              <input
                type="text"
                id="firstName"
                name="firstName"
                value={formData.firstName}
                onChange={handleChange}
                className={styles.input}
                placeholder="Иван"
                disabled={isLoading}
                autoComplete="given-name"
              />
            </div>

            <div className={styles.formGroup}>
              <label htmlFor="lastName" className={styles.label}>
                Фамилия
              </label>
              <input
                type="text"
                id="lastName"
                name="lastName"
                value={formData.lastName}
                onChange={handleChange}
                className={styles.input}
                placeholder="Иванов"
                disabled={isLoading}
                autoComplete="family-name"
              />
            </div>
          </div>

          <div className={styles.formGroup}>
            <label htmlFor="email" className={styles.label}>
              Email
            </label>
            <input
              type="email"
              id="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              className={styles.input}
              placeholder="ivan@example.com"
              disabled={isLoading}
              autoComplete="email"
            />
          </div>

          <div className={styles.formGroup}>
            <label htmlFor="phone" className={styles.label}>
              Номер телефона
            </label>
            <input
              type="tel"
              id="phone"
              name="phone"
              value={formData.phone}
              onChange={handlePhoneChange}
              className={styles.input}
              placeholder="+7 (___) ___-__-__"
              disabled={isLoading}
              autoComplete="tel"
            />
          </div>

          {error && <div className={styles.error}>{error}</div>}

          <button type="submit" className={styles.submitButton} disabled={isLoading}>
            {isLoading ? 'Отправка...' : 'Продолжить'}
          </button>

          <p className={styles.hint}>
            На ваш номер будет отправлен SMS-код для подтверждения
          </p>
        </form>

        <div className={styles.footer}>
          <p className={styles.footerText}>
            Уже есть аккаунт?{' '}
            <button
              type="button"
              onClick={handleLoginClick}
              className={styles.linkButton}
              disabled={isLoading}
            >
              Войти
            </button>
          </p>
        </div>
      </div>
    </div>
  );
};
