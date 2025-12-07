<?php

declare(strict_types=1);

namespace App\Internal\Handler;

use App\Auth\DTO\UserResponse;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[Route('/api/internal/v1/auth/validate', name: 'api_internal_auth_validate', methods: ['POST'])]
final class ValidateTokenHandler extends AbstractController
{
    public function __construct(
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly UserProviderInterface $userProvider,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        // Check internal API key
        $apiKey = $request->headers->get('X-Internal-API-Key');
        $expectedKey = $_ENV['INTERNAL_API_KEY'] ?? 'change_me_in_production';

        if ($apiKey !== $expectedKey) {
            return new JsonResponse(
                ['error' => 'Invalid API key'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;

        if (!$token) {
            return new JsonResponse(
                ['error' => 'Token required'],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $payload = $this->jwtManager->parse($token);
            $username = $payload['username'] ?? null;

            if (!$username) {
                return new JsonResponse(
                    ['error' => 'Invalid token payload'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $user = $this->userProvider->loadUserByIdentifier($username);

            if (!$user->isActive()) {
                return new JsonResponse(
                    ['error' => 'User is inactive'],
                    Response::HTTP_FORBIDDEN
                );
            }

            $userResponse = UserResponse::fromEntity($user);

            return new JsonResponse([
                'valid' => true,
                'user' => $userResponse->toArray(),
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => 'Invalid token', 'details' => $e->getMessage()],
                Response::HTTP_UNAUTHORIZED
            );
        }
    }
}
