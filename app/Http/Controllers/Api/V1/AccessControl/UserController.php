<?php

namespace App\Http\Controllers\Api\V1\AccessControl;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AccessControl\StoreUserRequest;
use App\Models\User;
use App\Tenancy\WorkspaceContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $workspaceId = app(WorkspaceContext::class)->id();
        $search = trim($filters['search'] ?? '');

        $users = User::query()
            ->join('workspace_user', 'workspace_user.user_id', '=', 'users.id')
            ->where('workspace_user.workspace_id', $workspaceId)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.username', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->select(['users.id', 'users.name', 'users.username', 'users.email', 'workspace_user.role', 'workspace_user.is_owner'])
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        $users->getCollection()->transform(function (User $user): User {
            $user->setAttribute('is_owner', (bool) $user->is_owner);
            $user->setAttribute('is_current', (int) $user->id === (int) request()->user()->id);
            return $user;
        });

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $workspaceId = app(WorkspaceContext::class)->id();

        $user = DB::transaction(function () use ($validated, $workspaceId): User {
            // New team accounts belong only to the active workspace, not automatically to Workspace Utama.
            $user = User::withoutEvents(fn (): User => User::query()->create([
                'name' => trim($validated['name']),
                'username' => strtolower(trim($validated['username'])),
                'email' => strtolower(trim($validated['email'])),
                'password' => $validated['password'],
                'role' => 'member',
            ]));

            DB::table('workspace_user')->insert([
                'workspace_id' => $workspaceId,
                'user_id' => $user->id,
                'role' => 'member',
                'is_owner' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });

        return response()->json([
            'user' => $user->only(['id', 'name', 'username', 'email', 'role']),
            'message' => 'Akun anggota berhasil dibuat.',
        ], JsonResponse::HTTP_CREATED);
    }

    public function updateRole(Request $request, int $userId): JsonResponse
    {
        $validated = $request->validate(['role' => ['required', 'in:admin,member']]);
        $workspaceId = app(WorkspaceContext::class)->id();
        $actorId = (int) $request->user()->id;

        $user = DB::transaction(function () use ($validated, $workspaceId, $actorId, $userId): User {
            $membership = DB::table('workspace_user')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $membership) {
                throw ValidationException::withMessages(['user' => 'Pengguna tidak ditemukan di workspace aktif.']);
            }

            if ((int) $userId === $actorId) {
                throw ValidationException::withMessages(['role' => 'Role akun yang sedang digunakan tidak dapat diubah dari halaman ini.']);
            }

            if ((bool) $membership->is_owner) {
                throw ValidationException::withMessages(['role' => 'Role pemilik workspace tidak dapat diubah.']);
            }

            if ($membership->role === 'admin' && $validated['role'] !== 'admin') {
                $adminCount = DB::table('workspace_user')
                    ->where('workspace_id', $workspaceId)
                    ->where('role', 'admin')
                    ->count();

                if ($adminCount <= 1) {
                    throw ValidationException::withMessages(['role' => 'Workspace harus memiliki setidaknya satu admin.']);
                }
            }

            DB::table('workspace_user')
                ->where('workspace_id', $workspaceId)
                ->where('user_id', $userId)
                ->update(['role' => $validated['role'], 'updated_at' => now()]);

            return User::query()->findOrFail($userId);
        });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $validated['role'],
            ],
            'message' => 'Role pengguna berhasil diperbarui.',
        ]);
    }
}
