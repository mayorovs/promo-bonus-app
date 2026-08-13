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
}
