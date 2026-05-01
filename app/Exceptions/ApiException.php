<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ApiException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $errors
     */
    public function __construct(
        string $message,
        public readonly string $errorCode = 'error',
        int $httpStatus = 400,
        public readonly ?array $errors = null,
    ) {
        parent::__construct($message, $httpStatus);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode,
            'errors' => $this->errors,
        ], $this->getCode() ?: 400);
    }
}
