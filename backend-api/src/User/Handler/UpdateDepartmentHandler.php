<?php

declare(strict_types=1);

namespace App\User\Handler;

use App\User\DTO\DepartmentResponse;
use App\User\DTO\UpdateDepartmentRequest;
use App\User\Exception\UserException;
use App\User\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/departments/{id}', name: 'api_v1_departments_update', methods: ['PATCH'])]
final class UpdateDepartmentHandler extends AbstractController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly Security $security,
        private readonly ValidatorInterface $validator,
        private readonly SerializerInterface $serializer,
    ) {}

    public function __invoke(int $id, Request $request): JsonResponse
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser) {
            return new JsonResponse(
                ['message' => 'Authentication required'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                UpdateDepartmentRequest::class,
                'json'
            );
        } catch (\Throwable) {
            return new JsonResponse(
                ['error' => 'Invalid JSON body'],
                Response::HTTP_BAD_REQUEST
            );
        }

        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            return new JsonResponse(
                ['error' => 'Validation failed', 'details' => (string) $errors],
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $department = $this->userService->updateDepartment($id, $dto, $currentUser);
            $response = DepartmentResponse::fromEntity($department);

            return new JsonResponse($response->toArray());
        } catch (UserException $e) {
            return new JsonResponse(
                ['error' => $e->getMessage()],
                $e->getStatusCode()
            );
        }
    }
}
