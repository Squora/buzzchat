<?php

declare(strict_types=1);

namespace App\Internal\Handler;

use App\User\DTO\UserProfileResponse;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/internal/users/{id}', name: 'api_internal_users_get', methods: ['GET'])]
final class GetUserHandler extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function __invoke(int $id, Request $request): JsonResponse
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

        try {
            $user = $this->userService->getUserProfile($id);
            $response = UserProfileResponse::fromEntity($user);

            return new JsonResponse($response->toArray());
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
