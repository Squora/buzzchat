<?php

declare(strict_types=1);

namespace App\Message\Handler;

use App\Message\DTO\AddReactionRequest;
use App\Message\Service\MessageService;
use App\Shared\DTO\SuccessResponse;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/messages/{id}/reactions', name: 'api_v1_messages_add_reaction', methods: ['POST'])]
final class AddReactionHandler extends AbstractController
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Add emoji reaction to message
     *
     * @throws ValidationException When request data is invalid
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

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            AddReactionRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $this->messageService->addReaction($id, $dto->emoji, $user);

        $response = new SuccessResponse(message: 'Reaction added successfully');

        return new JsonResponse($response->toArray());
    }
}
