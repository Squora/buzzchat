<?php

declare(strict_types=1);

namespace App\Auth\EventSubscriber;

use App\Auth\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'lexik_jwt_authentication.on_jwt_created' => 'onJWTCreated',
        ];
    }

    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $payload = $event->getData();

        $payload['user_id'] = $user->getId();
        $payload['email'] = $user->getEmail();
        $payload['first_name'] = $user->getFirstName();
        $payload['last_name'] = $user->getLastName();

        $event->setData($payload);
    }
}
