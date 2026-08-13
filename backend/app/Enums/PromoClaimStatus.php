<?php

namespace App\Enums;

enum PromoClaimStatus: string
{
    /** The bonus was credited to the player balance. */
    case Applied = 'applied';

    /** The attempt was refused for a business reason and moved no money. */
    case Rejected = 'rejected';

    /** A previously applied bonus was reversed. */
    case Revoked = 'revoked';
}
