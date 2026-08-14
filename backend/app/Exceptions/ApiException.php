<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A business refusal that must not persist anything.
 *
 * Rendering itself keeps the error contract identical to every other API
 * error: a stable machine-readable code plus a human-readable message.
 */
class ApiException extends Exception
{
    public function __construct(
        public readonly ApiErrorCode $errorCode,
        string $message,
        public readonly int $status,
    ) {
        parent::__construct($message);
    }

    public static function promoClaimNotFound(): self
    {
        // Deliberately identical for a claim that does not exist and one that
        // belongs to somebody else, so ownership cannot be probed.
        return new self(
            ApiErrorCode::PromoClaimNotFound,
            'This promo claim does not exist.',
            404,
        );
    }

    public static function promoClaimAlreadyRevoked(): self
    {
        return new self(
            ApiErrorCode::PromoClaimAlreadyRevoked,
            'This promo bonus has already been revoked.',
            409,
        );
    }

    public static function promoClaimNotRevocable(): self
    {
        return new self(
            ApiErrorCode::PromoClaimNotRevocable,
            'Only an applied promo bonus can be revoked.',
            409,
        );
    }

    public static function insufficientBalance(): self
    {
        return new self(
            ApiErrorCode::InsufficientBalance,
            'The balance is too low to revoke this bonus.',
            409,
        );
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'code' => $this->errorCode->value,
            'message' => $this->getMessage(),
        ], $this->status);
    }
}
