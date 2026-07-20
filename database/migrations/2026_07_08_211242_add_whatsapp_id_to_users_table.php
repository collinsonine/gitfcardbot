<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('whatsapp_id')->nullable()->after('phone_number');
        });

        DB::table('users')->where('phone_number', 'like', '%@%')->orderBy('id')->each(function ($user) {
            $original = $user->phone_number;
            $clean = preg_replace('/@(c\.us|lid|g\.us)$/', '', $original);

            $conflict = DB::table('users')
                ->where('phone_number', $clean)
                ->where('id', '!=', $user->id)
                ->first();

            if ($conflict) {
                DB::table('trades')->where('user_id', $user->id)->update(['user_id' => $conflict->id]);
                DB::table('chat_logs')->where('user_id', $user->id)->update(['user_id' => $conflict->id]);
                DB::table('users')->where('id', $conflict->id)->update(['whatsapp_id' => $original]);
                DB::table('users')->where('id', $user->id)->delete();
            } else {
                DB::table('users')->where('id', $user->id)->update([
                    'whatsapp_id' => $original,
                    'phone_number' => $clean,
                ]);
            }
        });

    }

    public function down(): void
    {
        DB::table('users')->whereNotNull('whatsapp_id')->orderBy('id')->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update([
                'phone_number' => $user->whatsapp_id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('whatsapp_id');
        });
    }
};
