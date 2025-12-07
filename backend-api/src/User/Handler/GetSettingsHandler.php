<?php

declare(strict_types=1);

namespace App\User\Handler;

use App\User\DTO\UserSettingsResponse;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/users/me/settings', name: 'api_v1_users_get_settings', methods: ['GET'])]
final class GetSettingsHandler extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security,
    ) {}

    public function __invoke(): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $settings = $this->userService->getUserSettings($currentUser);
        $response = UserSettingsResponse::fromEntity($settings);

        return new JsonResponse($response->toArray());
    }
}
