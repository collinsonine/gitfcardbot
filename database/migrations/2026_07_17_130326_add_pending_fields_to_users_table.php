<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('pending_card_type')->nullable()->after('trade_draft');
            $table->decimal('pending_amount', 10, 2)->nullable()->after('pending_card_type');
            $table->timestamp('pending_context_at')->nullable()->after('pending_amount');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['pending_card_type', 'pending_amount', 'pending_context_at']);
        });
    }
};
