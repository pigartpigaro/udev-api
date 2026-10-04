<?php

namespace App\Http\Controllers\Api\V1\Projects;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Projects\IndexProjectRequest;
use App\Http\Requests\Api\V1\Projects\StoreProjectRequest;
use App\Http\Requests\Api\V1\Projects\UpdateProjectRequest;
use App\Models\Projects\Project;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function index(IndexProjectRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $projects = Project::query()
            ->with(['customer:id,code,name', 'projectType:id,code,name'])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('projectType', fn ($type) => $type->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($projects);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create($request->validated())->load(['customer:id,code,name', 'projectType:id,code,name']);

        return response()->json(['project' => $project], Response::HTTP_CREATED);
    }

    public function show(Project $project): JsonResponse
    {
        return response()->json(['project' => $project->load(['customer:id,code,name', 'projectType:id,code,name'])]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project->update($request->validated());

        return response()->json(['project' => $project->fresh()->load(['customer:id,code,name', 'projectType:id,code,name'])]);
    }
}
