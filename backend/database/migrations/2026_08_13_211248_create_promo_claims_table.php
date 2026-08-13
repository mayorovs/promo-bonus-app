<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Null when the submitted code matched no promo code at all.
            $table->foreignId('promo_code_id')->nullable()->constrained()->restrictOnDelete();
            // What the player submitted, so a rejected attempt is still
            // meaningful when no promo code exists to point at.
            $table->string('submitted_code', 12);
            // Minor units. Null for an attempt that moved no money.
            $table->bigInteger('bonus_amount')->nullable();
            $table->string('status', 16);
            $table->string('rejection_reason', 32)->nullable();
            $table->timestamps();

            // The history endpoint lists one player's attempts, newest first.
            $table->index(['user_id', 'created_at']);
        });

        DB::statement(
            "ALTER TABLE promo_claims ADD CONSTRAINT promo_claims_status_valid
             CHECK (status IN ('applied', 'rejected', 'revoked'))"
        );

        DB::statement(
            "ALTER TABLE promo_claims ADD CONSTRAINT promo_claims_rejection_reason_valid
             CHECK (rejection_reason IS NULL OR rejection_reason IN (
                 'promo_code_not_found', 'promo_code_expired', 'already_claimed'
             ))"
        );

        // Same canonical form as promo_codes.code, so history stays comparable.
        DB::statement(
            'ALTER TABLE promo_claims ADD CONSTRAINT promo_claims_submitted_code_uppercase
             CHECK (submitted_code = upper(submitted_code))'
        );

        // A rejected attempt moved no money and must say why it was refused.
        DB::statement(
            "ALTER TABLE promo_claims ADD CONSTRAINT promo_claims_rejected_shape
             CHECK (status <> 'rejected' OR (bonus_amount IS NULL AND rejection_reason IS NOT NULL))"
        );

        // An applied or revoked claim always refers to a real promo code and a
        // positive amount, and has no rejection reason.
        DB::statement(
            "ALTER TABLE promo_claims ADD CONSTRAINT promo_claims_settled_shape
             CHECK (status = 'rejected' OR (
                 promo_code_id IS NOT NULL AND bonus_amount > 0 AND rejection_reason IS NULL
             ))"
        );

        // The core protection against double crediting: a player may hold at
        // most one applied or revoked claim per promo code. A revoked claim
        // keeps occupying the slot, so a reversed bonus can never be claimed
        // again. Rejected attempts are excluded and may therefore repeat.
        DB::statement(
            "CREATE UNIQUE INDEX promo_claims_one_settled_claim_per_player
             ON promo_claims (user_id, promo_code_id)
             WHERE status IN ('applied', 'revoked')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_claims');
    }
};
