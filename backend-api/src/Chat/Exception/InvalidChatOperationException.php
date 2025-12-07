<?php

declare(strict_types=1);

namespace App\Chat\Exception;

class InvalidChatOperationException extends ChatException
{
    protected string $messageKey = 'chat.invalid_operation';

    public static function cannotModifyDirectChat(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.cannot_modify_direct';
        return $exception;
    }

    public static function cannotRemoveSelf(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.cannot_remove_self';
        return $exception;
    }

    public static function cannotRemoveOwner(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.cannot_remove_owner';
        return $exception;
    }

    public static function cannotChangeOwnRole(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.cannot_change_own_role';
        return $exception;
    }

    public static function cannotChangeOwnerRole(): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.cannot_change_owner_role';
        return $exception;
    }

    public static function userAlreadyMember(int $userId): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.already_member';
        $exception->details = ['user_id' => $userId];
        return $exception;
    }

    public static function userNotMember(int $userId): self
    {
        $exception = new self();
        $exception->messageKey = 'chat.member_not_found';
        $exception->details = ['user_id' => $userId];
        return $exception;
    }
}
