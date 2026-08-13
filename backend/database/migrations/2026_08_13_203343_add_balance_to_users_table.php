<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The player balance is stored as an integer in minor units (for example,
     * cents), never as a floating-point value.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('balance')->default(0);
        });

        // A database-level constraint, because application checks alone cannot
        // prevent a negative balance under concurrent requests.
        DB::statement(
            'ALTER TABLE users ADD CONSTRAINT users_balance_non_negative CHECK (balance >= 0)'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_balance_non_negative');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('balance');
        });
    }
};
