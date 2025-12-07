<?php

declare(strict_types=1);

namespace App\Shared\Service;

/**
 * Service for translating error messages with localization support
 */
final readonly class ErrorTranslator
{
    private const TRANSLATIONS = [
        'en' => [
            // Auth errors
            'auth.invalid_credentials' => 'Invalid phone number or verification code',
            'auth.user_not_found' => 'User not found',
            'auth.user_already_exists' => 'User with this phone number already exists',
            'auth.verification_code_expired' => 'Verification code has expired',
            'auth.verification_code_invalid' => 'Invalid verification code',
            'auth.verification_not_found' => 'Verification request not found. Please register again',
            'auth.phone_invalid' => 'Invalid phone number format',
            'auth.too_many_attempts' => 'Too many attempts. Please try again later',
            'auth.user_inactive' => 'Your account is inactive. Please contact support',

            // Chat errors
            'chat.not_found' => 'Chat not found',
            'chat.access_denied' => 'Access denied to this chat',
            'chat.cannot_modify_direct' => 'Cannot modify direct chat',
            'chat.member_not_found' => 'Chat member not found',
            'chat.already_member' => 'User is already a member of this chat',
            'chat.owner_cannot_leave' => 'Chat owner cannot leave. Transfer ownership or delete the chat',
            'chat.direct_already_exists' => 'Direct chat with this user already exists',
            'chat.invalid_operation' => 'Invalid chat operation',
            'chat.cannot_remove_self' => 'Cannot remove yourself. Use leave endpoint instead',
            'chat.cannot_remove_owner' => 'Cannot remove chat owner',
            'chat.cannot_change_own_role' => 'Cannot change your own role',
            'chat.cannot_change_owner_role' => 'Cannot change owner role',
            'chat.not_member' => 'You are not a member of this chat',

            // Message errors
            'message.not_found' => 'Message not found',
            'message.access_denied' => 'Access denied to this message',
            'message.cannot_edit' => 'You can only edit your own messages',
            'message.cannot_delete' => 'You can only delete your own messages',

            // User errors
            'user.not_found' => 'User not found',
            'user.access_denied' => 'Access denied',
            'user.department_not_found' => 'Department not found',

            // Validation errors
            'validation.invalid_json' => 'Invalid JSON body',
            'validation.failed' => 'Validation failed',
            'validation.required_field' => 'This field is required',

            // General errors
            'error.authentication_required' => 'Authentication required',
            'error.internal_server' => 'Internal server error',
        ],
        'ru' => [
            // Auth errors
            'auth.invalid_credentials' => 'Неверный номер телефона или код подтверждения',
            'auth.user_not_found' => 'Пользователь не найден',
            'auth.user_already_exists' => 'Пользователь с таким номером телефона уже существует',
            'auth.verification_code_expired' => 'Код подтверждения истёк',
            'auth.verification_code_invalid' => 'Неверный код подтверждения',
            'auth.verification_not_found' => 'Запрос на верификацию не найден. Пожалуйста, зарегистрируйтесь снова',
            'auth.phone_invalid' => 'Неверный формат номера телефона',
            'auth.too_many_attempts' => 'Слишком много попыток. Попробуйте позже',
            'auth.user_inactive' => 'Ваш аккаунт неактивен. Обратитесь в поддержку',

            // Chat errors
            'chat.not_found' => 'Чат не найден',
            'chat.access_denied' => 'Доступ к этому чату запрещён',
            'chat.cannot_modify_direct' => 'Невозможно изменить личный чат',
            'chat.member_not_found' => 'Участник чата не найден',
            'chat.already_member' => 'Пользователь уже является участником этого чата',
            'chat.owner_cannot_leave' => 'Владелец не может покинуть чат. Передайте права владельца или удалите чат',
            'chat.direct_already_exists' => 'Личный чат с этим пользователем уже существует',
            'chat.invalid_operation' => 'Недопустимая операция с чатом',
            'chat.cannot_remove_self' => 'Нельзя удалить себя. Используйте выход из чата',
            'chat.cannot_remove_owner' => 'Нельзя удалить владельца чата',
            'chat.cannot_change_own_role' => 'Нельзя изменить свою роль',
            'chat.cannot_change_owner_role' => 'Нельзя изменить роль владельца',
            'chat.not_member' => 'Вы не являетесь участником этого чата',

            // Message errors
            'message.not_found' => 'Сообщение не найдено',
            'message.access_denied' => 'Доступ к этому сообщению запрещён',
            'message.cannot_edit' => 'Вы можете редактировать только свои сообщения',
            'message.cannot_delete' => 'Вы можете удалять только свои сообщения',

            // User errors
            'user.not_found' => 'Пользователь не найден',
            'user.access_denied' => 'Доступ запрещён',
            'user.department_not_found' => 'Отдел не найден',

            // Validation errors
            'validation.invalid_json' => 'Некорректный JSON',
            'validation.failed' => 'Ошибка валидации',
            'validation.required_field' => 'Это поле обязательно',

            // General errors
            'error.authentication_required' => 'Требуется аутентификация',
            'error.internal_server' => 'Внутренняя ошибка сервера',
        ],
    ];

    public function __construct(
        public string $defaultLocale = 'en',
    ) {}

    /**
     * Translate error message by key
     */
    public function translate(string $key, ?string $locale = null, array $parameters = []): string
    {
        $locale = $locale ?? $this->defaultLocale;

        $message = self::TRANSLATIONS[$locale][$key] ?? self::TRANSLATIONS['en'][$key] ?? $key;

        // Replace parameters in message (e.g., {user} -> John)
        foreach ($parameters as $param => $value) {
            $message = str_replace('{' . $param . '}', (string) $value, $message);
        }

        return $message;
    }

    /**
     * Get translated error response array
     */
    public function getErrorResponse(
        string $messageKey,
        ?string $locale = null,
        array $parameters = [],
        mixed $details = null
    ): array {
        $response = [
            'message' => $this->translate($messageKey, $locale, $parameters),
        ];

        if ($details !== null) {
            $response['details'] = $details;
        }

        return $response;
    }
}
