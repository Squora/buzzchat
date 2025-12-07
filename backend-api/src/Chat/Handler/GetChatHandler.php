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

#[Route('/api/v1/chats/{id}', name: 'api_v1_chats_get', methods: ['GET'])]
final class GetChatHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
    ) {}

    /**
     * Get chat details (without members list - use /api/v1/chats/{id}/members for that)
     */
    public function __invoke(int $id): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $chat = $this->chatService->getChat($id, $user);
        $response = ChatResponse::fromEntity($chat, currentUser: $user, includePreviews: false);

        return new JsonResponse($response->toArray());
    }
}
