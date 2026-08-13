<?php

namespace App\Enums;

/**
 * Stable, machine-readable error codes returned by the API.
 *
 * The frontend switches on these values; the accompanying message is meant for
 * humans and may be reworded without breaking clients.
 */
enum ApiErrorCode: string
{
    case ValidationFailed = 'VALIDATION_FAILED';
    case InvalidCredentials = 'INVALID_CREDENTIALS';
    case Unauthenticated = 'UNAUTHENTICATED';
    case PromoCodeNotFound = 'PROMO_CODE_NOT_FOUND';
    case PromoCodeExpired = 'PROMO_CODE_EXPIRED';
    case PromoCodeAlreadyClaimed = 'PROMO_CODE_ALREADY_CLAIMED';
}
