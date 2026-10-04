<?php

namespace App\Tenancy;

use Illuminate\Support\Facades\DB;

class WorkspaceContext
{
    private ?int $workspaceId = null;

    private ?string $role = null;

    public function activate(int $workspaceId, string $role): void
    {
        $this->workspaceId = $workspaceId;
        $this->role = $role;
    }

    public function clear(): void
    {
        $this->workspaceId = null;
        $this->role = null;
    }

    public function id(): ?int
    {
        if ($this->workspaceId !== null) {
            return $this->workspaceId;
        }

        if (app()->runningInConsole() && DB::getSchemaBuilder()->hasTable('workspaces')) {
            $defaultId = DB::table('workspaces')->where('slug', 'workspace-utama')->value('id');
            return $defaultId === null ? null : (int) $defaultId;
        }

        return null;
    }

    public function role(): ?string
    {
        return $this->role;
    }
}
