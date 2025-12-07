<?php

declare(strict_types=1);

namespace App\User\Handler;

use App\User\DTO\DepartmentResponse;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/departments', name: 'api_v1_departments_list', methods: ['GET'])]
final class GetAllDepartmentsHandler extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $activeOnly = $request->query->getBoolean('active_only', false);

        $departments = $this->userService->getAllDepartments($activeOnly);

        $response = array_map(
            fn($dept) => DepartmentResponse::fromEntity($dept)->toArray(),
            $departments
        );

        return new JsonResponse(['departments' => $response]);
    }
}
