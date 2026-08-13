<?php

namespace App\Models;

use Database\Factories\PromoCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['code', 'bonus_amount', 'expires_at'])]
class PromoCode extends Model
{
    /** @use HasFactory<PromoCodeFactory> */
    use HasFactory;

    /**
     * Promo codes are case-insensitive, which is implemented by reducing every
     * code to one canonical uppercase form on the way in and on lookup.
     */
    public static function normaliseCode(string $code): string
    {
        return Str::upper(trim($code));
    }

    public static function findByCode(string $code): ?self
    {
        return static::query()->where('code', static::normaliseCode($code))->first();
    }

    protected function code(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => static::normaliseCode($value),
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bonus_amount' => 'integer',
            'expires_at' => 'datetime',
        ];
    }
}
