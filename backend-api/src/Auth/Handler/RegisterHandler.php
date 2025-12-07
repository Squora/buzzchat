<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Auth\DTO\RegisterRequest;
use App\Auth\DTO\VerificationSentResponse;
use App\Auth\Exception\UserAlreadyExistsException;
use App\Auth\Service\AuthService;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth/register', name: 'api_v1_auth_register', methods: ['POST'])]
final class RegisterHandler extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Register a new user and send verification code
     *
     * @throws ValidationException When request data is invalid
     * @throws UserAlreadyExistsException When user with phone already exists
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RegisterRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $phone = $this->authService->startRegistration($dto);

        $response = new VerificationSentResponse(
            message: 'Verification code sent',
            phone: $phone
        );

        return new JsonResponse($response->toArray());
    }
}
