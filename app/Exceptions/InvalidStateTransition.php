<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Thrown by action jobs when a record cannot move from its current status to the requested one.
 * API requests receive a 422.
 */
class InvalidStateTransition extends RuntimeException
{
    public function __construct(string $message = 'This action is not allowed in the current status.')
    {
        parent::__construct($message);
    }

    public function render(Request $request): ?JsonResponse
    {
        if (!$request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $this->getMessage()], 422);
    }
}
