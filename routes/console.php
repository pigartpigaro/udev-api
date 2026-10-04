<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Workspace;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('udev:setup', function (): int {
    if (! app()->environment('local')) {
        $this->error('Perintah setup hanya boleh dijalankan pada APP_ENV=local.');
        return Command::FAILURE;
    }

    $this->info('Menjalankan migrasi database...');
    if ($this->call('migrate') !== Command::SUCCESS) {
        return Command::FAILURE;
    }

    $this->info('Mengisi menu, permission, role, dan data awal...');
    if ($this->call('db:seed') !== Command::SUCCESS) {
        return Command::FAILURE;
    }

    if (User::query()->where('is_platform_admin', true)->exists()) {
        $this->info('Setup selesai. Admin platform sudah ada; akun tidak diubah.');
        return Command::SUCCESS;
    }

    $name = $this->ask('Nama admin', 'Administrator');
    $username = $this->ask('Username admin', 'admin');
    $email = $this->ask('Email admin');

    $validated = Validator::make(
        ['name' => $name, 'username' => $username, 'email' => $email],
        [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ],
    )->validate();

    $password = $this->secret('Kata sandi admin (minimal 6 karakter)');
    $passwordConfirmation = $this->secret('Ulangi kata sandi admin');
    Validator::make(
        ['password' => $password, 'password_confirmation' => $passwordConfirmation],
        ['password' => ['required', 'string', 'min:6', 'max:255', 'confirmed']],
    )->validate();

    $admin = DB::transaction(fn (): User => User::create([
        'name' => trim($validated['name']),
        'username' => strtolower(trim($validated['username'])),
        'email' => strtolower(trim($validated['email'])),
        'password' => $password,
        'role' => 'admin',
        'is_platform_admin' => true,
    ]));

    $this->info('Setup selesai. Username admin: '.$admin->username);
    return Command::SUCCESS;
})->purpose('Migrate, seed initial data, and create the first local platform administrator');

Artisan::command('udev:make-admin', function (): void {
    $name = $this->ask('Nama admin');
    $email = $this->ask('Email admin');

    $validated = Validator::make(
        ['name' => $name, 'email' => $email],
        ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255', 'unique:users,email']],
    )->validate();

    $password = $this->secret('Kata sandi admin (minimal 6 karakter)');
    Validator::make(['password' => $password], [
        'password' => ['required', 'string', 'min:6', 'max:255'],
    ])->validate();

    $admin = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => $password,
        'role' => 'admin',
        'is_platform_admin' => true,
    ]);

    $this->info('Akun admin berhasil dibuat. Username: '.$admin->username);
})->purpose('Create the first UDev administrator account');

Artisan::command('udev:workspace:create', function (): void {
    $name = $this->ask('Nama workspace/perusahaan');
    $slug = Str::slug((string) $name);
    Validator::make(['name' => $name, 'slug' => $slug], [
        'name' => ['required', 'string', 'max:255'],
        'slug' => ['required', 'string', 'max:80', 'unique:workspaces,slug'],
    ])->validate();

    $email = $this->ask('Email pemilik workspace');
    Validator::make(['email' => $email], ['email' => ['required', 'email', 'max:255']])->validate();
    $owner = User::query()->where('email', $email)->first();
    $isNewUser = $owner === null;
    $ownerName = null;
    $password = null;

    if ($isNewUser) {
        $ownerName = $this->ask('Nama pemilik');
        Validator::make(['name' => $ownerName], ['name' => ['required', 'string', 'max:255']])->validate();
        $password = $this->secret('Kata sandi awal (minimal 6 karakter)');
        Validator::make(['password' => $password], ['password' => ['required', 'string', 'min:6', 'max:255']])->validate();
    } elseif (! $this->confirm('Akun '.$owner->email.' sudah ada. Jadikan anggota workspace sebagai pemilik?', false)) {
        $this->warn('Pembuatan workspace dibatalkan.');
        return;
    }

    DB::transaction(function () use ($name, $slug, $owner, $isNewUser, $email, $ownerName, $password): void {
        $workspace = Workspace::create(['name' => $name, 'slug' => $slug, 'is_active' => true]);
        $member = $owner ?? User::create([
            'name' => $ownerName,
            'email' => $email,
            'password' => $password,
            'role' => 'member',
            'is_platform_admin' => false,
        ]);

        if ($isNewUser) {
            DB::table('workspace_user')->where('user_id', $member->id)->where('workspace_id', '!=', $workspace->id)->delete();
        }
        $workspace->users()->syncWithoutDetaching([$member->id => ['role' => 'admin', 'is_owner' => true]]);

        $defaultWorkspaceId = Workspace::query()->where('slug', 'workspace-utama')->value('id');
        $platformPermissionIds = DB::table('permissions')->whereIn('key', [
            'access-control.menus.manage',
            'access-control.permissions.manage',
        ])->pluck('id');
        $assignments = DB::table('role_permissions')->where('workspace_id', $defaultWorkspaceId)
            ->where('role', 'admin')->whereNotIn('permission_id', $platformPermissionIds)
            ->pluck('permission_id')->map(fn (int $permissionId): array => [
                'workspace_id' => $workspace->id,
                'role' => 'admin',
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
        if ($assignments !== []) {
            DB::table('role_permissions')->insertOrIgnore($assignments);
        }

        $memberPermissions = DB::table('role_permissions')->where('workspace_id', $defaultWorkspaceId)
            ->where('role', 'member')->pluck('permission_id')->map(fn (int $permissionId): array => [
                'workspace_id' => $workspace->id,
                'role' => 'member',
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();
        if ($memberPermissions !== []) {
            DB::table('role_permissions')->insertOrIgnore($memberPermissions);
        }

        $projectTypes = DB::table('project_types')->where('workspace_id', $defaultWorkspaceId)->get(['name', 'description', 'is_active']);
        foreach ($projectTypes as $projectType) {
            DB::table('project_types')->insertOrIgnore([
                'workspace_id' => $workspace->id,
                'name' => $projectType->name,
                'description' => $projectType->description,
                'is_active' => $projectType->is_active,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $this->info('Workspace berhasil dibuat dan pemilik sudah ditambahkan.');
})->purpose('Provision a customer workspace and its owner');
