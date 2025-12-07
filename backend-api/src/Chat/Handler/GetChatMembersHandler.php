<?php

declare(strict_types=1);

namespace App\Chat\Handler;

use App\Chat\DTO\ChatMemberResponse;
use App\Chat\DTO\ChatMembersListRequest;
use App\Chat\DTO\ChatMembersListResponse;
use App\Chat\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/chats/{id}/members', name: 'api_v1_chats_members_list', methods: ['GET'])]
final class GetChatMembersHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
    ) {}

    /**
     * Get chat members with pagination and filters
     *
     * Query parameters:
     * - page: int (default: 1)
     * - limit: int (default: 50, max: 100)
     * - search: string (optional) - search by name or phone
     * - role: string (optional) - filter by role: owner, admin, member
     * - status: string (optional) - filter by online status: available, busy, away, offline
     * - sort_by: string (default: joined_at) - sort by: role, joined_at, name, online
     * - sort_order: string (default: asc) - asc or desc
     */
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Parse query parameters
        $requestDto = new ChatMembersListRequest(
            page: (int) $request->query->get('page', 1),
            limit: min((int) $request->query->get('limit', 50), 100),
            search: $request->query->get('search'),
            role: $request->query->get('role'),
            status: $request->query->get('status'),
            sortBy: $request->query->get('sort_by', 'joined_at'),
            sortOrder: $request->query->get('sort_order', 'asc'),
        );

        // Get members
        $result = $this->chatService->getChatMembers($id, $requestDto, $user);

        // Convert to response DTOs
        $memberResponses = array_map(
            fn($member) => ChatMemberResponse::fromEntity($member),
            $result['items']
        );

        $response = new ChatMembersListResponse(
            items: $memberResponses,
            page: $requestDto->page,
            limit: $requestDto->limit,
            total: $result['total'],
        );

        return new JsonResponse($response->toArray());
    }
}
