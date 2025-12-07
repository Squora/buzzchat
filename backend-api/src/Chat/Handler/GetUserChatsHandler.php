<?php

declare(strict_types=1);

namespace App\Chat\Handler;

use App\Chat\DTO\ChatListResponse;
use App\Chat\DTO\ChatResponse;
use App\Chat\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/chats', name: 'api_v1_chats_list', methods: ['GET'])]
final class GetUserChatsHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
    ) {}

    /**
     * Get all chats for current user (with member previews but not full member lists)
     */
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $chats = $this->chatService->getUserChats($user);
        $chatResponses = array_map(
            fn($chat) => ChatResponse::fromEntity($chat, currentUser: $user, includePreviews: true),
            $chats
        );

        $response = new ChatListResponse($chatResponses);

        return new JsonResponse($response->toArray());
    }
}
