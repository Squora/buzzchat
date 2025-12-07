<?php

declare(strict_types=1);

namespace App\User\Handler;

use App\User\DTO\UserProfileResponse;
use App\User\Exception\UserException;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/users/{id}/activate', name: 'api_v1_users_activate', methods: ['POST'])]
final class ActivateUserHandler extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security,
    ) {}

    public function __invoke(int $id): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $user = $this->userService->activateUser($id, $currentUser);
            $response = UserProfileResponse::fromEntity($user);

            return new JsonResponse($response->toArray());
        } catch (UserException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                $e->getStatusCode()
            );
        } catch (\Throwable $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST
            );
        }
    }
}
