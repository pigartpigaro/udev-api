<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TENANT_TABLES = [
        'customers',
        'project_types',
        'projects',
        'project_invoices',
        'project_payments',
        'project_expenses',
    ];

    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug', 80)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        $workspaceId = DB::table('workspaces')->insertGetId([
            'name' => 'Workspace Utama',
            'slug' => 'workspace-utama',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('workspace_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 30)->default('member');
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
            $table->index(['user_id', 'workspace_id']);
        });

        DB::table('users')->orderBy('id')->each(function (object $user) use ($workspaceId, $now): void {
            DB::table('workspace_user')->insert([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => $user->role ?: 'member',
                'is_owner' => $user->role === 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_platform_admin')->default(false)->after('role')->index();
        });
        $platformAdminId = DB::table('users')->where('role', 'admin')->orderBy('id')->value('id');
        if ($platformAdminId !== null) {
            DB::table('users')->where('id', $platformAdminId)->update(['is_platform_admin' => true]);
        }

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
        DB::table('role_permissions')->update(['workspace_id' => $workspaceId]);
        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropUnique(['role', 'permission_id']);
            $table->unique(['workspace_id', 'role', 'permission_id']);
            $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
        });

        foreach (self::TENANT_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('workspace_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
            DB::table($tableName)->update(['workspace_id' => $workspaceId]);
            Schema::table($tableName, function (Blueprint $table): void {
                $table->unsignedBigInteger('workspace_id')->nullable(false)->change();
            });
        }

        Schema::table('project_types', function (Blueprint $table): void {
            $table->dropUnique(['name']);
            $table->unique(['workspace_id', 'name']);
        });
    }

    public function down(): void
    {
        if (DB::table('workspaces')->count() > 1) {
            throw new RuntimeException('Workspace tenancy cannot be rolled back while more than one workspace exists.');
        }

        Schema::table('project_types', function (Blueprint $table): void {
            $table->dropUnique(['workspace_id', 'name']);
            $table->unique('name');
        });

        foreach (array_reverse(self::TENANT_TABLES) as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropConstrainedForeignId('workspace_id');
            });
        }

        Schema::table('role_permissions', function (Blueprint $table): void {
            $table->dropUnique(['workspace_id', 'role', 'permission_id']);
            $table->unique(['role', 'permission_id']);
            $table->dropConstrainedForeignId('workspace_id');
        });

        Schema::dropIfExists('workspace_user');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_platform_admin');
        });
        Schema::dropIfExists('workspaces');
    }
};
