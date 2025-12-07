<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Auth\DTO\TokenResponse;
use App\Auth\DTO\UserResponse;
use App\Auth\DTO\VerifyCodeRequest;
use App\Auth\Exception\InvalidVerificationCodeException;
use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Exception\VerificationNotFoundException;
use App\Auth\Service\AuthConfig;
use App\Auth\Service\AuthService;
use App\Shared\Exception\ValidationException;
use Carbon\Carbon;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth/register/verify', name: 'api_v1_auth_register_verify', methods: ['POST'])]
final class VerifyCodeHandler extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly AuthConfig $authConfig,
    ) {}

    /**
     * Verify registration code and create user account with JWT tokens
     *
     * @throws ValidationException When request data is invalid
     * @throws VerificationNotFoundException When verification request not found
     * @throws InvalidVerificationCodeException When code is invalid or expired
     * @throws UserAlreadyExistsException When user already exists (race condition)
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            VerifyCodeRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $user = $this->authService->verifyRegistrationCode($dto);

        // Generate JWT tokens
        $accessToken = $this->jwtManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            $this->authConfig->refreshTokenTtl
        );
        $this->refreshTokenManager->save($refreshToken);

        $tokenResponse = new TokenResponse(
            $accessToken,
            'Bearer',
            $this->authConfig->accessTokenTtl,
            $refreshToken->getRefreshToken()
        );

        $response = new JsonResponse($tokenResponse->toArray(), Response::HTTP_CREATED);

        // Set access token cookie (httpOnly, secure)
        $accessTokenCookie = Cookie::create('access_token')
            ->withValue($accessToken)
            ->withExpires(Carbon::now()->addSeconds($this->authConfig->accessTokenTtl))
            ->withPath('/')
            ->withSecure($_ENV['APP_ENV'] === 'prod') // Only HTTPS in production
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        // Set refresh token cookie (httpOnly, secure)
        $refreshTokenCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken->getRefreshToken())
            ->withExpires(Carbon::now()->addSeconds($this->authConfig->refreshTokenTtl))
            ->withPath('/')
            ->withSecure($_ENV['APP_ENV'] === 'prod') // Only HTTPS in production
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($accessTokenCookie);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }
}
