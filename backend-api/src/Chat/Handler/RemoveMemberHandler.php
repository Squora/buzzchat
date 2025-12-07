<?php

declare(strict_types=1);

namespace App\Chat\Handler;

use App\Chat\DTO\ChatResponse;
use App\Chat\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/chats/{id}/members/{userId}', name: 'api_v1_chats_remove_member', methods: ['DELETE'])]
final class RemoveMemberHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
    ) {}

    /**
     * Remove member from chat (admins only)
     */
    public function __invoke(int $id, int $userId): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $chat = $this->chatService->removeMember($id, $userId, $user);
        $response = ChatResponse::fromEntity($chat, currentUser: $user, includePreviews: false);

        return new JsonResponse($response->toArray());
    }
}
