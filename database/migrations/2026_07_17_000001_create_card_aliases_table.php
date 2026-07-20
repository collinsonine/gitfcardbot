<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias_word')->unique();
            $table->string('resolved_card');
            $table->unsignedInteger('hit_count')->default(0);
            $table->timestamps();

            $table->index('resolved_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_aliases');
    }
};
