<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_types', function (Blueprint $table): void {
            $table->string('code', 32)->nullable()->unique()->after('id');
        });

        DB::table('project_types')->orderBy('id')->chunkById(500, function ($projectTypes): void {
            foreach ($projectTypes as $projectType) {
                DB::table('project_types')->where('id', $projectType->id)->update([
                    'code' => 'U-TP'.str_pad((string) $projectType->id, 4, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('project_types', function (Blueprint $table): void {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
