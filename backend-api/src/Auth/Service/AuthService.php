<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Contracts\VerificationCodeSenderInterface;
use App\Auth\DTO\RegisterRequest;
use App\Auth\DTO\RequestLoginCodeRequest;
use App\Auth\DTO\VerifyCodeRequest;
use App\Auth\Entity\PendingUser;
use App\Auth\Entity\User;
use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Exception\InvalidVerificationCodeException;
use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Exception\UserInactiveException;
use App\Auth\Exception\VerificationNotFoundException;
use App\Auth\Repository\PendingUserRepository;
use App\Auth\Repository\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PendingUserRepository $pendingUserRepository,
        private readonly VerificationCodeSenderInterface $codeSender,
    ) {}

    /**
     * Starts user registration process
     * Creates pending user and sends verification code
     */
    public function startRegistration(RegisterRequest $dto): string
    {
        if ($this->userRepository->existsByPhone($dto->phone)) {
            throw UserAlreadyExistsException::withPhone($dto->phone);
        }

        if ($this->userRepository->existsByEmail($dto->email)) {
            throw UserAlreadyExistsException::withEmail($dto->email);
        }

        // Remove old pending registration if exists
        $existingPending = $this->pendingUserRepository->findByPhone($dto->phone);
        if ($existingPending) {
            $this->pendingUserRepository->remove($existingPending);
        }

        $code = $this->generateCode();

        $pendingUser = new PendingUser();
        $pendingUser->setPhone($dto->phone);
        $pendingUser->setEmail($dto->email);
        $pendingUser->setFirstName($dto->firstName);
        $pendingUser->setLastName($dto->lastName);
        $pendingUser->setVerificationCode($code);

        $this->pendingUserRepository->save($pendingUser);
        $this->codeSender->send($dto->phone, $code);

        return $dto->phone;
    }

    /**
     * Verifies code and creates user account
     */
    public function verifyRegistrationCode(VerifyCodeRequest $dto): User
    {
        $pendingUser = $this->pendingUserRepository->findByPhone($dto->phone);

        if (!$pendingUser) {
            throw VerificationNotFoundException::forPhone($dto->phone);
        }

        if (!$pendingUser->verifyCode($dto->code)) {
            throw InvalidVerificationCodeException::create();
        }

        if ($this->userRepository->existsByPhone($dto->phone)) {
            throw UserAlreadyExistsException::withPhone($dto->phone);
        }

        $user = new User();
        $user->setPhone($pendingUser->getPhone());
        $user->setEmail($pendingUser->getEmail());
        $user->setFirstName($pendingUser->getFirstName());
        $user->setLastName($pendingUser->getLastName());

        $this->userRepository->save($user);
        $this->pendingUserRepository->remove($pendingUser);

        return $user;
    }

    /**
     * Requests login code for existing user
     */
    public function requestLoginCode(RequestLoginCodeRequest $dto): string
    {
        $user = $this->userRepository->findByPhone($dto->phone);

        if (!$user) {
            throw InvalidCredentialsException::create();
        }

        if (!$user->isActive()) {
            throw UserInactiveException::create();
        }

        // Remove old pending login if exists
        $existingPending = $this->pendingUserRepository->findByPhone($dto->phone);
        if ($existingPending) {
            $this->pendingUserRepository->remove($existingPending);
        }

        $code = $this->generateCode();

        // Reuse PendingUser for login verification
        $pendingLogin = new PendingUser();
        $pendingLogin->setPhone($user->getPhone());
        $pendingLogin->setEmail($user->getEmail());
        $pendingLogin->setFirstName($user->getFirstName());
        $pendingLogin->setLastName($user->getLastName());
        $pendingLogin->setVerificationCode($code);

        $this->pendingUserRepository->save($pendingLogin);
        $this->codeSender->send($dto->phone, $code);

        return $dto->phone;
    }


    /**
     * Verifies login code and returns user
     *
     * @throws VerificationNotFoundException
     * @throws InvalidVerificationCodeException
     * @throws InvalidCredentialsException
     * @throws UserInactiveException
     */
    public function verifyLoginCode(VerifyCodeRequest $dto): User
    {
        $pendingLogin = $this->pendingUserRepository->findByPhone($dto->phone);

        if (!$pendingLogin) {
            throw VerificationNotFoundException::forPhone($dto->phone);
        }

        if (!$pendingLogin->verifyCode($dto->code)) {
            throw InvalidVerificationCodeException::create();
        }

        $user = $this->userRepository->findByPhone($dto->phone);

        if (!$user) {
            throw InvalidCredentialsException::create();
        }

        if (!$user->isActive()) {
            throw UserInactiveException::create();
        }

        // Remove pending login after successful verification
        $this->pendingUserRepository->remove($pendingLogin);

        return $user;
    }

    private function generateCode(): string
    {
        return (string) random_int(1000, 9999);
    }
}
