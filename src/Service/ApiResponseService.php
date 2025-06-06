<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseService
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function success(mixed $data, string $message = '', array $meta = []): JsonResponse
    {
        $jsonData = $this->serializer->serialize($data, 'json');

        return new JsonResponse([
            'status' => 'success',
            'code' => Response::HTTP_OK,
            'message' => $message,
            'data' => json_decode($jsonData, true), // 👈 important : convertir en tableau pour l'intégrer
            'meta' => $meta,
        ]);
    }

    public function error(string $message, int $code = Response::HTTP_BAD_REQUEST, array $errors = []): JsonResponse
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $message,
            'code' => $code,
            'errors' => $errors ?: null,
        ], $code);
    }

}
