<?php

declare(strict_types=1);

namespace App\Auth\EventSubscriber;

use App\Auth\Entity\User;
use App\Auth\Service\AuthConfig;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Transform refresh token response to OAuth2 format and set access_token cookie
 * Runs AFTER AuthenticationSuccessSubscriber and gesdinet's AttachRefreshTokenOnSuccessListener
 */
class RefreshTokenResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthConfig $authConfig,
        private readonly string $appEnv,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 5: Run AFTER other subscribers (default priority 0)
            'lexik_jwt_authentication.on_authentication_success' => ['onAuthenticationSuccess', 5],
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $data = $event->getData();
        $response = $event->getResponse();

        // Transform Lexik's format to OAuth2 format (only for refresh endpoint)
        if (isset($data['token'])) {
            $accessToken = $data['token'];

            // Create clean OAuth2-compliant response (no user, no refresh_token_expiration)
            $newData = [
                'access_token' => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $this->authConfig->accessTokenTtl,
            ];

            $event->setData($newData);

            // Set access_token cookie to match login endpoint behavior
            $accessTokenCookie = Cookie::create('access_token')
                ->withValue($accessToken)
                ->withExpires(time() + $this->authConfig->accessTokenTtl)
                ->withPath('/')
                ->withSecure($this->appEnv === 'prod')
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($accessTokenCookie);
        }
    }
}
