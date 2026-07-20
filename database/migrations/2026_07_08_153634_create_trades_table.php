<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('card_type');
            $table->decimal('amount_usd', 14, 2);
            $table->decimal('rate_paid', 10, 2);
            $table->decimal('customer_payout', 14, 2);
            $table->decimal('estimated_profit', 14, 2);
            $table->string('status')->default('pending');
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
