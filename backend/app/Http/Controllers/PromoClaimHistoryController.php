<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromoClaimHistoryRequest;
use App\Http\Resources\PromoClaimResource;
use App\Models\PromoClaim;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PromoClaimHistoryController extends Controller
{
    private const PER_PAGE = 10;

    public function index(PromoClaimHistoryRequest $request): AnonymousResourceCollection
    {
        $claims = PromoClaim::query()
            // Scoped to the authenticated player, so one player can never
            // read another player's history.
            ->where('user_id', $request->user()->getKey())
            ->when(
                $request->validated('status'),
                fn ($query, string $status) => $query->where('status', $status),
            )
            // The id breaks ties, so records created in the same instant keep
            // a stable order and pagination cannot repeat or skip a record.
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return PromoClaimResource::collection($claims);
    }
}
