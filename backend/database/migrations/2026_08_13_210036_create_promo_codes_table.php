<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_codes', function (Blueprint $table) {
            $table->id();
            // Twelve characters is the longest code the assignment allows.
            $table->string('code', 12)->unique();
            // Bonus amount in minor units, never a floating-point value.
            $table->bigInteger('bonus_amount');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        // Codes are compared case-insensitively. Storing a single canonical
        // uppercase form is what makes the unique index case-insensitive too,
        // so the database refuses anything that is not already uppercase
        // instead of relying on every writer remembering to normalise.
        DB::statement(
            'ALTER TABLE promo_codes ADD CONSTRAINT promo_codes_code_uppercase CHECK (code = upper(code))'
        );

        DB::statement(
            'ALTER TABLE promo_codes ADD CONSTRAINT promo_codes_bonus_amount_positive CHECK (bonus_amount > 0)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_codes');
    }
};
