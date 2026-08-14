<?php

namespace App\Http\Requests;

use App\Enums\PromoClaimStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoClaimHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The player is never accepted as input; only the optional filter is.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(PromoClaimStatus::class)],
        ];
    }
}
