<?php

declare(strict_types=1);

namespace App\Message\Handler;

use App\Message\DTO\MessageResponse;
use App\Message\DTO\SendMessageRequest;
use App\Message\Service\MessageService;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/messages', name: 'api_v1_messages_send', methods: ['POST'])]
final class SendMessageHandler extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Send a new message to a chat
     *
     * @throws ValidationException When request data is invalid
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            SendMessageRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $message = $this->messageService->sendMessage($dto, $user);
        $response = MessageResponse::fromEntity($message);

        return new JsonResponse($response->toArray(), Response::HTTP_CREATED);
    }
}
