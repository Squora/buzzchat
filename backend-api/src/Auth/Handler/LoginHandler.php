<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Auth\DTO\TokenResponse;
use App\Auth\DTO\VerifyCodeRequest;
use App\Auth\Exception\InvalidVerificationCodeException;
use App\Auth\Exception\UserInactiveException;
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

#[Route('/api/v1/auth/login/verify', name: 'api_v1_auth_login_verify', methods: ['POST'])]
final class LoginHandler extends AbstractController
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
     * Verify login code and issue JWT tokens
     *
     * @throws ValidationException When request data is invalid
     * @throws VerificationNotFoundException When verification request not found
     * @throws InvalidVerificationCodeException When code is invalid or expired
     * @throws UserInactiveException When user account is inactive
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

        $user = $this->authService->verifyLoginCode($dto);

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
            $refreshToken->getRefreshToken(),
        );

        $response = new JsonResponse($tokenResponse->toArray());

        $accessTokenCookie = Cookie::create('access_token')
            ->withValue($accessToken)
            ->withExpires(Carbon::now()->addSeconds($this->authConfig->accessTokenTtl))
            ->withPath('/')
            ->withSecure($_ENV['APP_ENV'] === 'prod') // Only HTTPS in production
            ->withHttpOnly()
            ->withSameSite(Cookie::SAMESITE_LAX);

        $refreshTokenCookie = Cookie::create('refresh_token')
            ->withValue($refreshToken->getRefreshToken())
            ->withExpires(Carbon::now()->addSeconds($this->authConfig->refreshTokenTtl))
            ->withPath('/')
            ->withSecure($_ENV['APP_ENV'] === 'prod') // Only HTTPS in production
            ->withHttpOnly()
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($accessTokenCookie);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }
}
