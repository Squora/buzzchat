<?php

declare(strict_types=1);

namespace App\User\Handler;

use App\User\DTO\UserProfileResponse;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/users', name: 'api_v1_users_list', methods: ['GET'])]
final class GetAllUsersHandler extends AbstractController
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

        // Get query parameters
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = max(1, min(100, (int) $request->query->get('limit', 20)));
        $search = $request->query->get('search');
        $departmentId = $request->query->get('department');
        $onlineStatus = $request->query->get('onlineStatus');
        $isActive = $request->query->get('isActive');

        // Build filters
        $filters = [];
        if ($search) {
            $filters['search'] = $search;
        }
        if ($departmentId) {
            $filters['department_id'] = (int) $departmentId;
        }
        if ($onlineStatus) {
            $filters['online_status'] = $onlineStatus;
        }
        if ($isActive !== null) {
            $filters['is_active'] = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
        }

        // Get paginated users
        $result = $this->userService->getPaginatedUsers($page, $limit, $filters);

        $response = [
            'data' => array_map(
                fn($user) => UserProfileResponse::fromEntity($user)->toArray(),
                $result['users']
            ),
            'meta' => [
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'totalPages' => (int) ceil($result['total'] / $limit),
            ],
        ];

        return new JsonResponse($response);
    }
}
