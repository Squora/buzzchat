<?php

declare(strict_types=1);

namespace App\Message\Handler;

use App\Message\DTO\MessageListResponse;
use App\Message\DTO\MessageResponse;
use App\Message\Service\MessageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/chats/{chatId}/messages', name: 'api_v1_messages_get', methods: ['GET'])]
final class GetMessagesHandler extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly Security $security,
    ) {}

    /**
     * Get messages for a chat with pagination
     */
    public function __invoke(int $chatId, Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $beforeId = $request->query->get('before_id') ? (int) $request->query->get('before_id') : null;
        $limit = $request->query->get('limit', 50);
        $limit = min((int) $limit, 100); // Max 100

        $messages = $this->messageService->getMessages($chatId, $user, $beforeId, $limit);

        $messageResponses = array_map(
            fn($msg) => MessageResponse::fromEntity($msg),
            $messages
        );

        $response = new MessageListResponse(
            messages: $messageResponses,
            hasMore: count($messages) === $limit
        );

        return new JsonResponse($response->toArray());
    }
}
