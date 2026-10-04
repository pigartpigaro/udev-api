<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 32)->nullable()->unique()->after('name');
        });

        $taken = [];
        DB::table('users')->orderBy('id')->get(['id', 'email'])->each(function (object $user) use (&$taken): void {
            $localPart = strtolower((string) strstr($user->email, '@', true));
            $base = trim((string) preg_replace('/[^a-z0-9._-]+/', '', $localPart), '._-');
            $base = substr($base !== '' ? $base : 'user'.$user->id, 0, 24);
            $username = $base;
            $suffix = 1;

            while (isset($taken[$username])) {
                $username = substr($base, 0, 24).$suffix;
                $suffix++;
            }

            $taken[$username] = true;
            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 32)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
