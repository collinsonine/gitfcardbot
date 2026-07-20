<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone_number')->unique()->nullable()->after('name');
            $table->string('chat_state')->default('idle')->after('phone_number');
            $table->integer('invalid_option_count')->default(0)->after('chat_state');
            $table->boolean('is_bot_paused')->default(false)->after('invalid_option_count');

            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone_number', 'chat_state', 'invalid_option_count', 'is_bot_paused']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->unique()->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
