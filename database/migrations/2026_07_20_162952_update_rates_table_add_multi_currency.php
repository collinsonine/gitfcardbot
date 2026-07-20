<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->decimal('usd_ngn', 10, 2)->default(0)->after('card_name');
            $table->decimal('gbp_ngn', 10, 2)->default(0)->after('usd_ngn');
            $table->decimal('eur_ngn', 10, 2)->default(0)->after('gbp_ngn');
        });

        DB::table('rates')->update([
            'usd_ngn' => DB::raw('buying_rate'),
        ]);

        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn(['buying_rate', 'selling_rate']);
        });
    }

    public function down(): void
    {
        Schema::table('rates', function (Blueprint $table) {
            $table->decimal('buying_rate', 10, 2)->default(0)->after('card_name');
            $table->decimal('selling_rate', 10, 2)->default(0)->after('buying_rate');
        });

        DB::table('rates')->update([
            'buying_rate' => DB::raw('usd_ngn'),
            'selling_rate' => DB::raw('usd_ngn'),
        ]);

        Schema::table('rates', function (Blueprint $table) {
            $table->dropColumn(['usd_ngn', 'gbp_ngn', 'eur_ngn']);
        });
    }
};
