<?php

declare(strict_types=1);

namespace App\Internal\Handler;

use App\Auth\DTO\UserResponse;
use App\Chat\Repository\ChatMemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/internal/v1/chats/{chatId}/members', name: 'api_internal_chats_members', methods: ['GET'])]
final class GetChatMembersHandler extends AbstractController
{
    public function __construct(
        private readonly ChatMemberRepository $chatMemberRepository,
    ) {}

    public function __invoke(int $chatId, Request $request): JsonResponse
    {
        // Check internal API key
        $apiKey = $request->headers->get('X-Internal-API-Key');
        $expectedKey = $_ENV['INTERNAL_API_KEY'] ?? 'change_me_in_production';

        if ($apiKey !== $expectedKey) {
            return new JsonResponse(
                ['error' => 'Invalid API key'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $members = $this->chatMemberRepository->findActiveMembersByChat($chatId);

        $users = array_map(
            function ($member) {
                return UserResponse::fromEntity($member->getUser())->toArray();
            },
            $members
        );

        return new JsonResponse([
            'chat_id' => $chatId,
            'members' => $users,
        ]);
    }
}
