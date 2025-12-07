import React from 'react';
import styles from './AuthLayout.module.scss';

interface AuthLayoutProps {
  children: React.ReactNode;
}

export const AuthLayout: React.FC<AuthLayoutProps> = ({ children }) => {
  return (
    <div className={styles.authLayout}>
      <div className={styles.background}>
        <div className={styles.brandSection}>
          <div className={styles.brandContent}>
            <div className={styles.logo}>
              <span className={styles.logoIcon}>💬</span>
              <span className={styles.logoText}>BuzzChat</span>
            </div>
            <h2 className={styles.brandTitle}>Корпоративный чат для вашей команды</h2>
            <p className={styles.brandDescription}>
              Общайтесь, делитесь файлами и работайте вместе в реальном времени
            </p>
          </div>
        </div>
        <div className={styles.formSection}>{children}</div>
      </div>
    </div>
  );
};
