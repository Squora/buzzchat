<?php

declare(strict_types=1);

namespace App\Chat\Handler;

use App\Chat\DTO\AddMembersRequest;
use App\Chat\DTO\ChatResponse;
use App\Chat\Service\ChatService;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/chats/{id}/members', name: 'api_v1_chats_add_members', methods: ['POST'])]
final class AddMembersHandler extends AbstractController
{
    public function __construct(
        private readonly ChatService $chatService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Add members to chat (admins only)
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
            AddMembersRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $chat = $this->chatService->addMembers($id, $dto, $user);
        $response = ChatResponse::fromEntity($chat, currentUser: $user, includePreviews: false);

        return new JsonResponse($response->toArray());
    }
}
