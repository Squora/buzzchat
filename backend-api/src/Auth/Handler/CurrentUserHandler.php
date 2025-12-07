<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Auth\DTO\UserResponse;
use App\Auth\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/auth/current', name: 'api_v1_auth_current', methods: ['GET'])]
final class CurrentUserHandler extends AbstractController
{
    /**
     * Get current authenticated user
     */
    public function __invoke(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse([
                'message' => 'Authentication required',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $response = UserResponse::fromEntity($user);

        return new JsonResponse($response->toArray());
    }
}