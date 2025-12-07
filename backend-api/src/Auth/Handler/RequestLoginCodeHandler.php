<?php

declare(strict_types=1);

namespace App\Auth\Handler;

use App\Auth\DTO\RequestLoginCodeRequest;
use App\Auth\DTO\VerificationSentResponse;
use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Exception\UserInactiveException;
use App\Auth\Service\AuthService;
use App\Shared\Exception\ValidationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/auth/login/request-code', name: 'api_v1_auth_request_login_code', methods: ['POST'])]
final class RequestLoginCodeHandler extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    /**
     * Request login verification code for existing user
     *
     * @throws ValidationException When request data is invalid
     * @throws InvalidCredentialsException When phone or password is incorrect
     * @throws UserInactiveException When user account is inactive
     */
    public function __invoke(Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            RequestLoginCodeRequest::class,
            'json'
        );

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }

        $phone = $this->authService->requestLoginCode($dto);

        $response = new VerificationSentResponse(
            message: 'Verification code sent',
            phone: $phone
        );

        return new JsonResponse($response->toArray());
    }
}
