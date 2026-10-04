<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Tenancy\WorkspaceContext;

class WorkspaceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaces = DB::table('workspace_user')
            ->join('workspaces', 'workspaces.id', '=', 'workspace_user.workspace_id')
            ->where('workspace_user.user_id', $request->user()->id)
            ->where('workspaces.is_active', true)
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name', 'workspaces.slug', 'workspace_user.role', 'workspace_user.is_owner']);

        return response()->json(['workspaces' => $workspaces]);
    }

    public function current(): JsonResponse
    {
        $workspace = Workspace::query()->findOrFail(app(WorkspaceContext::class)->id());

        return response()->json(['workspace' => $workspace->only(['id', 'name', 'slug'])]);
    }

    public function updateCurrent(UpdateWorkspaceRequest $request): JsonResponse
    {
        $workspace = Workspace::query()->findOrFail(app(WorkspaceContext::class)->id());
        $workspace->update(['name' => trim($request->validated('name'))]);

        return response()->json(['workspace' => $workspace->fresh()->only(['id', 'name', 'slug'])]);
    }
}
