<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->string('code', 32)->nullable()->unique()->after('id');
        });

        DB::table('customers')->orderBy('id')->chunkById(500, function ($customers): void {
            foreach ($customers as $customer) {
                DB::table('customers')->where('id', $customer->id)->update([
                    'code' => 'U-PL'.str_pad((string) $customer->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
