<?php

namespace App\Models;

use App\Enums\PromoClaimRejectionReason;
use App\Enums\PromoClaimStatus;
use Database\Factories\PromoClaimFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'promo_code_id',
    'submitted_code',
    'bonus_amount',
    'status',
    'rejection_reason',
])]
class PromoClaim extends Model
{
    /** @use HasFactory<PromoClaimFactory> */
    use HasFactory;

    public function player(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Null when the submitted code matched no promo code.
     */
    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class);
    }

    protected function submittedCode(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => PromoCode::normaliseCode($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bonus_amount' => 'integer',
            'status' => PromoClaimStatus::class,
            'rejection_reason' => PromoClaimRejectionReason::class,
        ];
    }
}
