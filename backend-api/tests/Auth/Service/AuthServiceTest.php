<?php

declare(strict_types=1);

namespace App\Tests\Auth\Service;

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
use App\Auth\Repository\PendingUserRepository;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\AuthService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    private UserRepository&MockObject $userRepository;
    private PendingUserRepository&MockObject $pendingUserRepository;
    private VerificationCodeSenderInterface&MockObject $codeSender;
    private AuthService $authService;

    protected function setUp(): void
    {
        // Arrange - Create mocks
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->pendingUserRepository = $this->createMock(PendingUserRepository::class);
        $this->codeSender = $this->createMock(VerificationCodeSenderInterface::class);

        $this->authService = new AuthService(
            $this->userRepository,
            $this->pendingUserRepository,
            $this->codeSender
        );
    }

    #[Test]
    public function startRegistration_WithValidData_CreatesPendingUserAndSendsCode(): void
    {
        // Arrange
        $dto = new RegisterRequest();
        $dto->phone = '+79991234567';
        $dto->email = 'test@example.com';
        $dto->firstName = 'John';
        $dto->lastName = 'Doe';

        $this->userRepository->expects($this->once())->method('existsByPhone')->with($dto->phone)->willReturn(false);
        $this->userRepository->expects($this->once())->method('existsByEmail')->with($dto->email)->willReturn(false);
        $this->pendingUserRepository->expects($this->once())->method('findByPhone')->willReturn(null);
        $this->pendingUserRepository->expects($this->once())->method('save')->with($this->isInstanceOf(PendingUser::class));
        $this->codeSender->expects($this->once())->method('send')->with($dto->phone, $this->matchesRegularExpression('/^\d{4}$/'));

        // Act
        $result = $this->authService->startRegistration($dto);

        // Assert
        $this->assertEquals($dto->phone, $result);
    }

    #[Test]
    public function startRegistration_WithExistingPhone_ThrowsException(): void
    {
        // Arrange
        $dto = new RegisterRequest();
        $dto->phone = '+79991234567';
        $dto->email = 'test@example.com';
        $dto->firstName = 'John';
        $dto->lastName = 'Doe';

        $this->userRepository->method('existsByPhone')->willReturn(true);

        // Assert
        $this->expectException(UserAlreadyExistsException::class);

        // Act
        $this->authService->startRegistration($dto);
    }

    #[Test]
    public function verifyRegistrationCode_WithValidCode_CreatesUser(): void
    {
        // Arrange
        $dto = new VerifyCodeRequest();
        $dto->phone = '+79991234567';
        $dto->code = '1234';

        $pendingUser = new PendingUser();
        $pendingUser->setPhone($dto->phone);
        $pendingUser->setEmail('test@example.com');
        $pendingUser->setFirstName('John');
        $pendingUser->setLastName('Doe');
        $pendingUser->setVerificationCode($dto->code);

        $this->pendingUserRepository->method('findByPhone')->willReturn($pendingUser);
        $this->userRepository->method('existsByPhone')->willReturn(false);
        $this->userRepository->expects($this->once())->method('save');
        $this->pendingUserRepository->expects($this->once())->method('remove');

        // Act
        $result = $this->authService->verifyRegistrationCode($dto);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($dto->phone, $result->getPhone());
    }

    #[Test]
    #[DataProvider('invalidCodeProvider')]
    public function verifyRegistrationCode_WithInvalidCode_ThrowsException(string $validCode, string $providedCode): void
    {
        // Arrange
        $dto = new VerifyCodeRequest();
        $dto->phone = '+79991234567';
        $dto->code = $providedCode;

        $pendingUser = new PendingUser();
        $pendingUser->setPhone($dto->phone);
        $pendingUser->setEmail('test@example.com');
        $pendingUser->setFirstName('John');
        $pendingUser->setLastName('Doe');
        $pendingUser->setVerificationCode($validCode);

        $this->pendingUserRepository->method('findByPhone')->willReturn($pendingUser);

        // Assert
        $this->expectException(InvalidVerificationCodeException::class);

        // Act
        $this->authService->verifyRegistrationCode($dto);
    }

    public static function invalidCodeProvider(): array
    {
        return [
            'wrong code' => ['1234', '5678'],
            'different length' => ['1234', '12345'],
        ];
    }

    #[Test]
    public function requestLoginCode_WithValidUser_SendsCode(): void
    {
        // Arrange
        $dto = new RequestLoginCodeRequest();
        $dto->phone = '+79991234567';

        $user = new User();
        $user->setPhone($dto->phone);
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setIsActive(true);

        $this->userRepository->method('findByPhone')->willReturn($user);
        $this->pendingUserRepository->expects($this->once())->method('save');
        $this->codeSender->expects($this->once())->method('send');

        // Act
        $result = $this->authService->requestLoginCode($dto);

        // Assert
        $this->assertEquals($dto->phone, $result);
    }

    #[Test]
    public function requestLoginCode_WithNonExistentUser_ThrowsException(): void
    {
        // Arrange
        $dto = new RequestLoginCodeRequest();
        $dto->phone = '+79991234567';

        $this->userRepository->method('findByPhone')->willReturn(null);

        // Assert
        $this->expectException(InvalidCredentialsException::class);

        // Act
        $this->authService->requestLoginCode($dto);
    }

    #[Test]
    public function requestLoginCode_WithInactiveUser_ThrowsException(): void
    {
        // Arrange
        $dto = new RequestLoginCodeRequest();
        $dto->phone = '+79991234567';

        $user = new User();
        $user->setPhone($dto->phone);
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setIsActive(false);

        $this->userRepository->method('findByPhone')->willReturn($user);

        // Assert
        $this->expectException(UserInactiveException::class);

        // Act
        $this->authService->requestLoginCode($dto);
    }

    #[Test]
    public function verifyLoginCode_WithValidCode_ReturnsUser(): void
    {
        // Arrange
        $dto = new VerifyCodeRequest();
        $dto->phone = '+79991234567';
        $dto->code = '1234';

        $user = new User();
        $user->setPhone($dto->phone);
        $user->setEmail('test@example.com');
        $user->setFirstName('John');
        $user->setLastName('Doe');
        $user->setIsActive(true);

        $pendingLogin = new PendingUser();
        $pendingLogin->setPhone($dto->phone);
        $pendingLogin->setEmail('test@example.com');
        $pendingLogin->setFirstName('John');
        $pendingLogin->setLastName('Doe');
        $pendingLogin->setVerificationCode($dto->code);

        $this->pendingUserRepository->method('findByPhone')->willReturn($pendingLogin);
        $this->userRepository->method('findByPhone')->willReturn($user);
        $this->pendingUserRepository->expects($this->once())->method('remove');

        // Act
        $result = $this->authService->verifyLoginCode($dto);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($dto->phone, $result->getPhone());
    }

    #[Test]
    public function verifyLoginCode_WithInvalidCode_ThrowsException(): void
    {
        // Arrange
        $dto = new VerifyCodeRequest();
        $dto->phone = '+79991234567';
        $dto->code = '9999';

        $pendingLogin = new PendingUser();
        $pendingLogin->setPhone($dto->phone);
        $pendingLogin->setEmail('test@example.com');
        $pendingLogin->setFirstName('John');
        $pendingLogin->setLastName('Doe');
        $pendingLogin->setVerificationCode('1234');

        $this->pendingUserRepository->method('findByPhone')->willReturn($pendingLogin);

        // Assert
        $this->expectException(InvalidVerificationCodeException::class);

        // Act
        $this->authService->verifyLoginCode($dto);
    }
}
