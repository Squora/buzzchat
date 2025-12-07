# Автоматическая конвертация snake_case ↔ camelCase

## Реализация

Добавлена автоматическая конвертация между snake_case (backend) и camelCase (frontend) с использованием библиотеки `humps` и Axios interceptors.

## Установленные пакеты

```json
{
  "dependencies": {
    "humps": "^2.0.1"
  },
  "devDependencies": {
    "@types/humps": "^2.0.6"
  }
}
```

## Как это работает

### 1. Request Interceptor (исходящие запросы)

Автоматически конвертирует все данные из **camelCase → snake_case** перед отправкой на backend:

```typescript
// Входящие данные (frontend)
{
  firstName: "Ivan",
  photoUrl: "https://..."
}

// ⬇️ Автоматически конвертируется

// Отправляемые данные (backend)
{
  first_name: "Ivan",
  photo_url: "https://..."
}
```

### 2. Response Interceptor (входящие ответы)

Автоматически конвертирует все данные из **snake_case → camelCase** при получении от backend:

```typescript
// Ответ от backend
{
  first_name: "Ivan",
  last_name: "Ivanov",
  photo_url: "https://...",
  created_at: "2025-01-15T10:00:00Z"
}

// ⬇️ Автоматически конвертируется

// Данные во frontend
{
  firstName: "Ivan",
  lastName: "Ivanov",
  photoUrl: "https://...",
  createdAt: "2025-01-15T10:00:00Z"
}
```

## Обновленные типы

Все TypeScript типы теперь используют **camelCase**:

### ✅ User Types (`src/features/user/types/user.types.ts`)
```typescript
interface User {
  firstName: string;
  lastName: string;
  photoUrl: string | null;
  onlineStatus: string;
  // ...
}
```

### ✅ Auth Types (`src/features/auth/types/auth.types.ts`)
```typescript
interface User {
  firstName: string | null;
  lastName: string | null;
  fullName: string;
  isActive: boolean;
}

interface VerifyRegisterResponse {
  refreshToken: string;
  // ...
}
```

### ✅ Chat Types (`src/features/chat/types/chat.types.ts`)
```typescript
interface Chat {
  photoUrl: string | null;
  membersCount: number;
  createdAt: string;
  updatedAt: string | null;
}

interface ChatMember {
  userId: number;
  userName: string;
  userAvatar?: string;
  joinedAt: string;
}
```

### ✅ Message Types (`src/features/message/types/message.types.ts`)
```typescript
interface Message {
  chatId: number;
  userId: number;
  userName: string;
  userAvatar?: string;
  createdAt: string;
  updatedAt: string | null;
  isEdited: boolean;
}

interface MessageAttachment {
  fileUrl: string;
  fileName: string;
  fileSize: number;
  fileType: string;
  thumbnailUrl?: string | null;
}
```

## Обновленные компоненты

### Исправленные ссылки на поля:

- `ChatArea.tsx`: `chat.photo_url` → `chat.photoUrl`
- `Sidebar.tsx`: `chat.photo_url` → `chat.photoUrl`
- `ChatSettingsModal.tsx`: `chat.photo_url` → `chat.photoUrl`, `chat.members_count` → `chat.membersCount`
- `MessageAttachments.tsx`: все поля вложений обновлены на camelCase

## Преимущества

✅ **Автоматическая конвертация** - не нужно вручную преобразовывать данные
✅ **Type Safety** - TypeScript типы соответствуют JavaScript conventions
✅ **Прозрачность** - разработчики работают только с camelCase
✅ **Централизованная логика** - вся конвертация в одном месте (apiClient.ts)
✅ **Консистентность** - все API вызовы обрабатываются одинаково

## Что НЕ конвертируется

- HTTP заголовки (остаются как есть)
- Cookies (остаются как есть)
- URL paths (остаются как есть)
- Специальные ключи (можно настроить исключения при необходимости)

## Файл конфигурации

**Файл:** `src/api/v1/apiClient.ts`

```typescript
import { camelizeKeys, decamelizeKeys } from 'humps';

// Request interceptor - camelCase → snake_case
apiClientV1.interceptors.request.use((config) => {
  if (config.data && typeof config.data === 'object') {
    config.data = decamelizeKeys(config.data);
  }
  if (config.params && typeof config.params === 'object') {
    config.params = decamelizeKeys(config.params);
  }
  return config;
});

// Response interceptor - snake_case → camelCase
apiClientV1.interceptors.response.use((response) => {
  if (response.data && typeof response.data === 'object') {
    response.data = camelizeKeys(response.data);
  }
  return response;
});
```

## Тестирование

### Проверка автоконвертации:

1. **Загрузка пользователей** - откройте модальное окно "Новое сообщение"
   - Проверьте, что отображаются `firstName`, `lastName`
   - Проверьте, что `photoUrl` и `onlineStatus` работают корректно

2. **Создание чата** - создайте новую группу или личный чат
   - Проверьте, что данные отправляются и возвращаются корректно

3. **Просмотр чата** - откройте любой чат
   - Проверьте отображение `photoUrl`, `membersCount`
   - Проверьте отображение сообщений с `createdAt`, `isEdited`

4. **Вложения** - если есть сообщения с файлами
   - Проверьте, что `fileUrl`, `fileName`, `fileSize` отображаются

## Миграция существующего кода

Если у вас есть старый код, который использует snake_case:

### До:
```typescript
const name = user.first_name;
const url = chat.photo_url;
const count = chat.members_count;
```

### После:
```typescript
const name = user.firstName;
const url = chat.photoUrl;
const count = chat.membersCount;
```

## Отладка

Если конвертация не работает:

1. Проверьте импорт `humps` в `apiClient.ts`
2. Убедитесь, что interceptors зарегистрированы до любых API вызовов
3. Проверьте console для ошибок
4. Добавьте console.log в interceptors для отладки:

```typescript
apiClientV1.interceptors.request.use((config) => {
  console.log('Before conversion:', config.data);
  if (config.data && typeof config.data === 'object') {
    config.data = decamelizeKeys(config.data);
  }
  console.log('After conversion:', config.data);
  return config;
});
```

## Альтернативы

Если по какой-то причине `humps` не подходит, можно использовать:

1. **camelcase-keys + snakecase-keys** (более модульный подход)
2. **Custom utility** (без зависимостей, ~30 строк кода)
3. **lodash** (если уже используется в проекте)

## Ссылки

- [humps на npm](https://www.npmjs.com/package/humps)
- [humps на GitHub](https://github.com/domchristie/humps)
- [Axios Interceptors Docs](https://axios-http.com/docs/interceptors)
