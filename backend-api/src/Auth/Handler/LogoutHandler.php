<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Shared\DTO\SuccessResponse;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/auth/logout', name: 'api_v1_auth_logout', methods: ['POST'])]
final class LogoutHandler extends AbstractController
{
    public function __construct(
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
    ) {}

    /**
     * Logout and invalidate refresh token
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Read refresh token from cookie
        $refreshToken = $request->cookies->get('refresh_token');

        if ($refreshToken) {
            try {
                $token = $this->refreshTokenManager->get($refreshToken);

                if ($token) {
                    $this->refreshTokenManager->delete($token);
                }
            } catch (\Exception) {
                // Ignore errors - we're logging out anyway
            }
        }

        $responseDto = new SuccessResponse(message: 'Logged out successfully');
        $response = new JsonResponse($responseDto->toArray());

        // Clear both cookies
        $response->headers->setCookie(
            Cookie::create('access_token')
                ->withValue('')
                ->withExpires(new \DateTime('-1 year'))
                ->withPath('/')
                ->withHttpOnly(true)
        );

        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue('')
                ->withExpires(new \DateTime('-1 year'))
                ->withPath('/')
                ->withHttpOnly(true)
        );

        return $response;
    }
}
