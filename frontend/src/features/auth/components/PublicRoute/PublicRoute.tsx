import React from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useAuth } from '../../context/AuthContext';
import styles from './PublicRoute.module.scss';

interface PublicRouteProps {
  children: React.ReactNode;
}

/**
 * PublicRoute - only allows access for unauthenticated users
 * Authenticated users are redirected to the main app (/)
 * Used for /login, /register pages
 */
export const PublicRoute: React.FC<PublicRouteProps> = ({ children }) => {
  const { isAuthenticated, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return (
      <div className={styles.loadingContainer}>
        <div className={styles.spinner}></div>
        <p className={styles.loadingText}>Загрузка...</p>
      </div>
    );
  }

  if (isAuthenticated) {
    // Redirect authenticated users to main app
    // Try to go to the page they wanted, or default to home
    const from = (location.state as any)?.from?.pathname || '/';
    return <Navigate to={from} replace />;
  }

  return <>{children}</>;
};
