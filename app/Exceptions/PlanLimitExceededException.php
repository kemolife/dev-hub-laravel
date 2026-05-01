<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class PlanLimitExceededException extends RuntimeException
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function forPosts(): self
    {
        return new self('You have reached your monthly post limit. Upgrade to Pro for unlimited posts.');
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'upgrade_url' => route('billing.checkout'),
        ], 422);
    }
}
