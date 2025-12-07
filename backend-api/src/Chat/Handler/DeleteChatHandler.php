<?php

declare(strict_types=1);

namespace App\Chat\Handler;

use App\Chat\Service\ChatService;
use App\Shared\DTO\SuccessResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/chats/{id}', name: 'api_v1_chats_delete', methods: ['DELETE'])]
final class DeleteChatHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
    ) {}

    /**
     * Delete chat (owner only)
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

        $this->chatService->deleteChat($id, $user);

        $response = new SuccessResponse(message: 'Chat deleted successfully');

        return new JsonResponse($response->toArray());
    }
}
