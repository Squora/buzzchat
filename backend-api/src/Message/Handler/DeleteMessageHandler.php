<?php

declare(strict_types=1);

namespace App\Message\Handler;

use App\Message\Service\MessageService;
use App\Shared\DTO\SuccessResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/messages/{id}', name: 'api_v1_messages_delete', methods: ['DELETE'])]
final class DeleteMessageHandler extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly Security $security,
    ) {}

    /**
     * Delete message (author only)
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

        $this->messageService->deleteMessage($id, $user);

        $response = new SuccessResponse(message: 'Message deleted successfully');

        return new JsonResponse($response->toArray());
    }
}
