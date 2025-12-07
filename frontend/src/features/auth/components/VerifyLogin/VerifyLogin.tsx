import { useState, useRef, useEffect } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuth } from '@/features/auth/context/AuthContext';
import styles from './VerifyLogin.module.scss';

interface LocationState {
  phone?: string;
  mode?: string;
}

export const VerifyLogin = () => {
  const navigate = useNavigate();
  const location = useLocation();
  const { verifyLogin } = useAuth();

  const state = location.state as LocationState;
  const phone = state?.phone;

  const [code, setCode] = useState<string[]>(['', '', '', '']);
  const [error, setError] = useState<string>('');
  const [isLoading, setIsLoading] = useState(false);

  const inputRefs = [
    useRef<HTMLInputElement>(null),
    useRef<HTMLInputElement>(null),
    useRef<HTMLInputElement>(null),
    useRef<HTMLInputElement>(null),
  ];

  useEffect(() => {
    if (!phone) {
      navigate('/login');
    } else {
      inputRefs[0].current?.focus();
    }
  }, [phone, navigate]);

  const handleChange = (index: number, value: string) => {
    if (value && !/^\d$/.test(value)) {
      return;
    }

    const newCode = [...code];
    newCode[index] = value;
    setCode(newCode);
    setError('');

    if (value && index < 3) {
      inputRefs[index + 1].current?.focus();
    }

    if (value && index === 3 && newCode.every((digit) => digit !== '')) {
      handleSubmit(newCode.join(''));
    }
  };

  const handleKeyDown = (index: number, e: React.KeyboardEvent<HTMLInputElement>) => {
    if (e.key === 'Backspace' && !code[index] && index > 0) {
      inputRefs[index - 1].current?.focus();
    }
  };

  const handlePaste = (e: React.ClipboardEvent) => {
    e.preventDefault();
    const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 4);
    const newCode = pastedData.split('');

    while (newCode.length < 4) {
      newCode.push('');
    }

    setCode(newCode);

    const focusIndex = Math.min(pastedData.length, 3);
    inputRefs[focusIndex].current?.focus();

    if (pastedData.length === 4) {
      handleSubmit(pastedData);
    }
  };

  const handleSubmit = async (codeValue?: string) => {
    const fullCode = codeValue || code.join('');

    if (fullCode.length !== 4) {
      setError('Введите 4-значный код');
      return;
    }

    if (!phone) {
      setError('Номер телефона не найден');
      return;
    }

    setIsLoading(true);

    try {
      await verifyLogin(phone, fullCode);
      navigate('/');
    } catch (err: any) {
      console.error('Verification error:', err);
      if (err.response?.status === 400) {
        setError('Неверный код');
      } else if (err.response?.status === 404) {
        setError('Код истек. Пожалуйста, запросите новый код');
      } else if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Ошибка при проверке кода. Попробуйте позже');
      }
      setCode(['', '', '', '']);
      inputRefs[0].current?.focus();
    } finally {
      setIsLoading(false);
    }
  };

  const formatPhone = (phoneNumber: string): string => {
    if (phoneNumber.startsWith('+7') && phoneNumber.length === 12) {
      return `+7 (${phoneNumber.slice(2, 5)}) ${phoneNumber.slice(5, 8)}-${phoneNumber.slice(8, 10)}-${phoneNumber.slice(10, 12)}`;
    }
    return phoneNumber;
  };

  const handleBackClick = () => {
    navigate('/login');
  };

  return (
    <div className={styles.verify}>
      <div className={styles.verifyCard}>
        <div className={styles.header}>
          <h1 className={styles.title}>Подтверждение входа</h1>
          <p className={styles.subtitle}>
            Введите 4-значный код из SMS
            <br />
            <strong>{phone ? formatPhone(phone) : ''}</strong>
          </p>
        </div>

        <div className={styles.form}>
          <div className={styles.codeInputs} onPaste={handlePaste}>
            {code.map((digit, index) => (
              <input
                key={index}
                ref={inputRefs[index]}
                type="text"
                inputMode="numeric"
                maxLength={1}
                value={digit}
                onChange={(e) => handleChange(index, e.target.value)}
                onKeyDown={(e) => handleKeyDown(index, e)}
                className={styles.codeInput}
                disabled={isLoading}
                autoComplete="off"
              />
            ))}
          </div>

          {error && <div className={styles.error}>{error}</div>}

          <button
            type="button"
            onClick={() => handleSubmit()}
            className={styles.submitButton}
            disabled={isLoading || code.some((digit) => !digit)}
          >
            {isLoading ? 'Проверка...' : 'Войти'}
          </button>

          <p className={styles.hint}>Код действителен в течение 10 минут</p>
        </div>

        <div className={styles.footer}>
          <button
            type="button"
            onClick={handleBackClick}
            className={styles.backButton}
            disabled={isLoading}
          >
            Вернуться назад
          </button>
        </div>
      </div>
    </div>
  );
};
