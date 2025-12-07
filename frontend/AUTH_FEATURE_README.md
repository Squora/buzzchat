# Authentication Feature

## Обзор

Реализована полноценная система авторизации/аутентификации для корпоративного веб-чата BuzzChat с интеграцией с backend API.

## Архитектура

Фича разработана по **feature-based** принципу с полной изоляцией компонентов, стилей и бизнес-логики.

### Структура файлов

```
src/
├── features/
│   └── auth/
│       ├── components/
│       │   ├── Login/
│       │   │   ├── Login.tsx
│       │   │   └── Login.module.scss
│       │   ├── Register/
│       │   │   ├── Register.tsx
│       │   │   └── Register.module.scss
│       │   ├── VerifyCode/
│       │   │   ├── VerifyCode.tsx
│       │   │   └── VerifyCode.module.scss
│       │   ├── AuthLayout/
│       │   │   ├── AuthLayout.tsx
│       │   │   └── AuthLayout.module.scss
│       │   ├── ProtectedRoute/
│       │   │   ├── ProtectedRoute.tsx
│       │   │   └── ProtectedRoute.module.scss
│       │   └── index.ts
│       ├── context/
│       │   └── AuthContext.tsx
│       ├── services/
│       │   └── authService.ts
│       ├── types/
│       │   └── auth.types.ts
│       └── index.ts
├── api/
│   └── apiClient.ts
├── config/
│   └── env.ts
└── pages/
    └── MainChat/
        ├── MainChat.tsx
        └── MainChat.module.scss
```

## Основные компоненты

### 1. API Client (`src/api/apiClient.ts`)

Axios-клиент с автоматической обработкой:
- Добавление JWT токена в заголовки
- Автоматическое обновление токена при получении 401
- Перенаправление на страницу входа при провале refresh

### 2. Auth Service (`src/features/auth/services/authService.ts`)

Сервис для работы с API авторизации:
- `register()` - регистрация пользователя (отправка SMS)
- `verifyCode()` - подтверждение SMS-кода
- `login()` - вход по номеру телефона и паролю
- `getCurrentUser()` - получение текущего пользователя
- `refreshToken()` - обновление access токена
- `logout()` - выход из системы
- `storeAuthData()` / `clearAuthData()` - управление токенами в localStorage

### 3. AuthContext (`src/features/auth/context/AuthContext.tsx`)

React Context для управления состоянием авторизации:
- `user` - данные текущего пользователя
- `isAuthenticated` - статус авторизации
- `isLoading` - состояние загрузки
- `login()` - метод входа
- `register()` - метод регистрации
- `verifyCode()` - метод верификации
- `logout()` - метод выхода
- `refreshAuth()` - обновление данных пользователя

### 4. Компоненты

#### Login (`src/features/auth/components/Login/`)
- Форма входа с номером телефона и паролем
- Автоформатирование номера телефона: `+7 (XXX) XXX-XX-XX`
- Валидация на клиенте
- Обработка ошибок с backend

#### Register (`src/features/auth/components/Register/`)
- Форма регистрации с полями:
  - Номер телефона (обязательно)
  - Пароль (обязательно, мин. 6 символов)
  - Подтверждение пароля
  - Имя и фамилия (опционально)
- Автоформатирование телефона
- Валидация паролей
- Переход на страницу верификации после успешной регистрации

#### VerifyCode (`src/features/auth/components/VerifyCode/`)
- Ввод 4-значного SMS-кода
- Автофокус и автопереход между полями
- Поддержка вставки кода из буфера обмена
- Автоотправка при вводе последней цифры
- Таймер истечения кода (10 минут)

#### AuthLayout (`src/features/auth/components/AuthLayout/`)
- Обертка для страниц авторизации
- Красивый дизайн с брендингом
- Адаптивная верстка (десктоп/мобайл)

#### ProtectedRoute (`src/features/auth/components/ProtectedRoute/`)
- HOC для защиты роутов
- Перенаправление на `/login` для неавторизованных
- Показ загрузчика при проверке авторизации

## Интеграция с Backend

### API Endpoints

Все запросы идут на `http://localhost:8000/api/v1` (настраивается через `.env`)

1. **POST /auth/register**
   ```json
   {
     "phone": "79991234567",
     "password": "password123",
     "firstName": "Иван",
     "lastName": "Иванов"
   }
   ```
   Ответ:
   ```json
   {
     "message": "SMS код отправлен",
     "phone": "79991234567",
     "expiresAt": "2025-01-15T12:10:00Z"
   }
   ```

2. **POST /auth/verify**
   ```json
   {
     "phone": "79991234567",
     "code": "1234"
   }
   ```
   Ответ:
   ```json
   {
     "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
     "refreshToken": "refresh_token_here",
     "user": {
       "id": 1,
       "phone": "79991234567",
       "firstName": "Иван",
       "lastName": "Иванов",
       ...
     }
   }
   ```

3. **POST /auth/login**
   ```json
   {
     "phone": "79991234567",
     "password": "password123"
   }
   ```

4. **GET /auth/current**
   Требует JWT в заголовке: `Authorization: Bearer <token>`

5. **POST /auth/refresh**
   ```json
   {
     "refreshToken": "refresh_token_here"
   }
   ```

6. **POST /auth/logout**
   ```json
   {
     "refreshToken": "refresh_token_here"
   }
   ```

### Хранение токенов

Токены хранятся в `localStorage`:
- `accessToken` - JWT токен (короткий срок жизни)
- `refreshToken` - Refresh токен (длинный срок жизни)
- `user` - JSON с данными пользователя

## Использование

### 1. Настройка окружения

Создайте `.env` файл:
```env
VITE_API_BASE_URL=http://localhost:8000/api/v1
```

### 2. Использование AuthProvider

В `App.tsx` уже настроено:
```tsx
<AuthProvider>
  <Routes>
    {/* ... routes */}
  </Routes>
</AuthProvider>
```

### 3. Использование useAuth хука

В любом компоненте:
```tsx
import { useAuth } from './features/auth';

function MyComponent() {
  const { user, isAuthenticated, logout } = useAuth();

  return (
    <div>
      {isAuthenticated && (
        <div>
          <p>Привет, {user?.firstName}!</p>
          <button onClick={logout}>Выйти</button>
        </div>
      )}
    </div>
  );
}
```

### 4. Защита роутов

```tsx
<Route
  path="/protected"
  element={
    <ProtectedRoute>
      <YourComponent />
    </ProtectedRoute>
  }
/>
```

### 5. Использование authService напрямую

```tsx
import { authService } from './features/auth';

// Проверка авторизации
if (authService.isAuthenticated()) {
  // ...
}

// Получение пользователя из localStorage
const user = authService.getStoredUser();
```

## Стилизация

Все компоненты используют CSS Modules (`.module.scss`):
- Полная изоляция стилей
- Адаптивный дизайн
- Красивые градиенты и анимации
- Поддержка светлой темы (можно добавить темную)

### Цветовая схема
- Primary: `#667eea` → `#764ba2` (градиент)
- Background: белый
- Text: `#1a1a1a`, `#666`, `#999`
- Error: `#c33`

## Тестирование

### Регистрация в dev-режиме

Backend логирует SMS-коды в консоль:
```
[2025-01-15 12:00:00] DEV SMS to 79991234567: Code 1234
```

Найдите код в логах backend:
```bash
cd backend-api
tail -f var/log/dev.log | grep "DEV SMS"
```

### Примерный флоу

1. Перейдите на `/register`
2. Заполните форму:
   - Телефон: `+7 (999) 123-45-67`
   - Пароль: `test123`
   - Подтверждение: `test123`
3. Нажмите "Зарегистрироваться"
4. Проверьте backend логи для получения SMS-кода
5. Введите 4-значный код
6. Вы автоматически войдете в систему

## Безопасность

✅ **Реализовано:**
- JWT токены с коротким временем жизни
- Refresh токены для обновления access токенов
- Автоматическое обновление токенов при 401
- HTTPS рекомендуется для production
- Валидация на клиенте и сервере
- Очистка токенов при выходе

⚠️ **Рекомендации для production:**
- Использовать HTTPS
- Добавить rate limiting для регистрации
- Добавить CAPTCHA для предотвращения спама
- Хранить refresh токены в HttpOnly cookies (вместо localStorage)
- Добавить логирование попыток входа
- Настроить CORS правильно

## Дальнейшее развитие

Возможные улучшения:
- [ ] Восстановление пароля
- [ ] Двухфакторная аутентификация
- [ ] Социальные сети (OAuth)
- [ ] Запомнить меня
- [ ] История активных сессий
- [ ] Темная тема
- [ ] Интернационализация (i18n)
- [ ] E2E тесты (Playwright)
- [ ] Unit тесты (Vitest + React Testing Library)

## Troubleshooting

### Ошибка CORS
Убедитесь, что backend настроен правильно в `config/packages/nelmio_cors.yaml`:
```yaml
nelmio_cors:
    defaults:
        origin_regex: true
        allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
        allow_methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
        allow_headers: ['Content-Type', 'Authorization']
```

### Токены не сохраняются
Проверьте консоль браузера на ошибки и убедитесь, что:
- localStorage доступен
- Backend возвращает правильный формат ответа

### Бесконечная перезагрузка
Проверьте, что AuthProvider правильно обрабатывает состояние загрузки и не создает циклических зависимостей.
