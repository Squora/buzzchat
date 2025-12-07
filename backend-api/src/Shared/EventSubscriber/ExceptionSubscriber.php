<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use App\Shared\Exception\DomainExceptionInterface;
use App\Shared\Exception\ValidationException;
use App\Shared\Service\ErrorTranslator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Global exception handler for API
 * Automatically translates domain exceptions to localized JSON responses
 */
final class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ErrorTranslator $errorTranslator,
        private readonly LoggerInterface $logger,
        private readonly string $environment,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        // Handle authentication exceptions (missing/invalid token) -> 401
        if ($exception instanceof AuthenticationException) {
            $this->handleAuthenticationException($exception, $event);
            return;
        }

        // Handle access denied exceptions (authenticated but no permission) -> 403
        if ($exception instanceof AccessDeniedException) {
            $this->handleAccessDeniedException($exception, $event);
            return;
        }

        // Handle validation exceptions with constraint details
        if ($exception instanceof ValidationException) {
            $this->handleValidationException($exception, $event);
            return;
        }

        // Handle domain exceptions with automatic translation
        if ($exception instanceof DomainExceptionInterface) {
            $this->handleDomainException($exception, $event);
            return;
        }

        // Handle HTTP exceptions (like 404, 405, etc.)
        if ($exception instanceof HttpExceptionInterface) {
            $this->handleHttpException($exception, $event);
            return;
        }

        // Handle all other exceptions
        $this->handleGenericException($exception, $event);
    }

    private function handleAuthenticationException(AuthenticationException $exception, ExceptionEvent $event): void
    {
        $responseData = [
            'message' => 'Authentication required',
        ];

        $response = new JsonResponse($responseData, Response::HTTP_UNAUTHORIZED);
        $event->setResponse($response);
    }

    private function handleAccessDeniedException(AccessDeniedException $exception, ExceptionEvent $event): void
    {
        // Check if user is authenticated by looking at the token in request
        $request = $event->getRequest();
        $token = $request->headers->get('Authorization')
            ?? $request->cookies->get('access_token');

        // If no token provided, return 401 instead of 403
        if (!$token) {
            $responseData = [
                'message' => 'Authentication required',
            ];
            $statusCode = Response::HTTP_UNAUTHORIZED;
        } else {
            // User is authenticated but doesn't have permission
            $responseData = [
                'message' => 'Access denied',
            ];
            $statusCode = Response::HTTP_FORBIDDEN;
        }

        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }

    private function handleValidationException(ValidationException $exception, ExceptionEvent $event): void
    {
        // Build response with translated message and validation details
        $responseData = $this->errorTranslator->getErrorResponse(
            $exception->getMessageKey(),
            null, // TODO: Get locale from request
            [],
            $exception->getDetails()
        );

        $response = new JsonResponse($responseData, $exception->getStatusCode());
        $event->setResponse($response);
    }

    private function handleDomainException(DomainExceptionInterface $exception, ExceptionEvent $event): void
    {
        // Log domain exceptions based on severity
        $statusCode = $exception->getStatusCode();
        if ($statusCode >= 500) {
            $this->logger->error('Domain exception occurred', [
                'exception' => $exception->getMessage(),
                'messageKey' => $exception->getMessageKey(),
                'statusCode' => $statusCode,
            ]);
        }

        // Build response with translated message
        $responseData = $this->errorTranslator->getErrorResponse(
            $exception->getMessageKey(),
            null, // TODO: Get locale from request
            [],
            $exception->getDetails()
        );

        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }

    private function handleHttpException(HttpExceptionInterface $exception, ExceptionEvent $event): void
    {
        $statusCode = $exception->getStatusCode();

        $responseData = [
            'message' => $exception->getMessage() ?: Response::$statusTexts[$statusCode] ?? 'An error occurred',
        ];

        // In dev mode, add debug info
        if ($this->environment === 'dev') {
            $responseData['debug'] = [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ];
        }

        $response = new JsonResponse($responseData, $statusCode, $exception->getHeaders());
        $event->setResponse($response);
    }

    private function handleGenericException(\Throwable $exception, ExceptionEvent $event): void
    {
        // Log all unexpected exceptions
        $this->logger->error('Unexpected exception occurred', [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

        // In production, hide implementation details
        if ($this->environment === 'prod') {
            $responseData = $this->errorTranslator->getErrorResponse('error.internal_server');
        } else {
            // In dev mode, show detailed error information
            $responseData = [
                'message' => $exception->getMessage(),
                'details' => [
                    'exception' => get_class($exception),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString()),
                ],
            ];
        }

        $response = new JsonResponse($responseData, $statusCode);
        $event->setResponse($response);
    }
}
