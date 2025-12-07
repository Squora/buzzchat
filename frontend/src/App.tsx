import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import {
  AuthProvider,
  Login,
  Register,
  VerifyRegister,
  VerifyLogin,
  AuthLayout,
  ProtectedRoute,
} from '@/features/auth';
import { PublicRoute } from '@/features/auth/components/PublicRoute/PublicRoute';
import { MainChat } from '@/pages/MainChat/MainChat';
import '@/styles/global.scss';

const App = () => {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          {/* Auth routes - only for unauthenticated users */}
          <Route
            path="/login"
            element={
              <PublicRoute>
                <AuthLayout>
                  <Login />
                </AuthLayout>
              </PublicRoute>
            }
          />
          <Route
            path="/register"
            element={
              <PublicRoute>
                <AuthLayout>
                  <Register />
                </AuthLayout>
              </PublicRoute>
            }
          />
          <Route
            path="/verify-register"
            element={
              <AuthLayout>
                <VerifyRegister />
              </AuthLayout>
            }
          />
          <Route
            path="/verify-login"
            element={
              <AuthLayout>
                <VerifyLogin />
              </AuthLayout>
            }
          />

          {/* Protected routes */}
          <Route
            path="/"
            element={
              <ProtectedRoute>
                <MainChat />
              </ProtectedRoute>
            }
          />

          {/* Redirect unknown routes to home */}
          <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
};

export default App;
