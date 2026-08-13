<?php

namespace App\Enums;

/**
 * Why a promo code attempt was refused for a business reason.
 *
 * Malformed input is not represented here: it is rejected by request
 * validation before any attempt is recorded.
 */
enum PromoClaimRejectionReason: string
{
    case PromoCodeNotFound = 'promo_code_not_found';
    case PromoCodeExpired = 'promo_code_expired';
    case AlreadyClaimed = 'already_claimed';
}
