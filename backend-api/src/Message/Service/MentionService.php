<?php

declare(strict_types=1);

namespace App\Message\Service;

use App\Auth\Repository\UserRepository;

final class MentionService
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    /**
     * Extract @mentions from text and return array of user IDs
     */
    public function extractMentions(string $text): array
    {
        // Match @username pattern (alphanumeric + underscore)
        preg_match_all('/@(\w+)/', $text, $matches);

        if (empty($matches[1])) {
            return [];
        }

        $usernames = array_unique($matches[1]);
        $userIds = [];

        foreach ($usernames as $username) {
            // Try to find user by phone or email (simplified)
            // In real app, you might have a username field
            $user = $this->userRepository->createQueryBuilder('u')
                ->where('u.phone LIKE :username')
                ->orWhere('u.email LIKE :username')
                ->orWhere('CONCAT(u.firstName, u.lastName) LIKE :username')
                ->setParameter('username', '%' . $username . '%')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($user) {
                $userIds[] = $user->getId();
            }
        }

        return array_unique($userIds);
    }

    /**
     * Replace @mentions with clickable links in HTML
     */
    public function formatMentions(string $text, array $mentions): string
    {
        if (empty($mentions)) {
            return $text;
        }

        $users = $this->userRepository->findBy(['id' => $mentions]);
        $replacements = [];

        foreach ($users as $user) {
            $replacements['@' . $user->getPhone()] = sprintf(
                '<span class="mention" data-user-id="%d">@%s</span>',
                $user->getId(),
                $user->getFullName()
            );
        }

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
