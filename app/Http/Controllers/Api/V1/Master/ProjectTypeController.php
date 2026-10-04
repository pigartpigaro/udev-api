<?php

namespace App\Http\Controllers\Api\V1\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Master\IndexProjectTypeRequest;
use App\Http\Requests\Api\V1\Master\StoreProjectTypeRequest;
use App\Http\Requests\Api\V1\Master\UpdateProjectTypeRequest;
use App\Models\Master\ProjectType;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProjectTypeController extends Controller
{
    public function index(IndexProjectTypeRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $projectTypes = ProjectType::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('is_active', $filters), fn ($query) => $query->where('is_active', $filters['is_active']))
            ->orderBy('name')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return response()->json($projectTypes);
    }

    public function store(StoreProjectTypeRequest $request): JsonResponse
    {
        $projectType = ProjectType::create($request->validated());

        return response()->json(['project_type' => $projectType], Response::HTTP_CREATED);
    }

    public function show(ProjectType $projectType): JsonResponse
    {
        return response()->json(['project_type' => $projectType]);
    }

    public function update(UpdateProjectTypeRequest $request, ProjectType $projectType): JsonResponse
    {
        $projectType->update($request->validated());

        return response()->json(['project_type' => $projectType->fresh()]);
    }
}
