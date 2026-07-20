<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->json('media_paths')->nullable()->after('estimated_profit');
            $table->text('bank_details')->nullable()->after('media_paths');
            $table->string('payment_receipt_path')->nullable()->after('bank_details');
            $table->text('admin_notes')->nullable()->after('payment_receipt_path');
            $table->timestamp('paid_at')->nullable()->after('admin_notes');
        });
    }

    public function down(): void
    {
        Schema::table('trades', function (Blueprint $table) {
            $table->dropColumn(['media_paths', 'bank_details', 'payment_receipt_path', 'admin_notes', 'paid_at']);
        });
    }
};
