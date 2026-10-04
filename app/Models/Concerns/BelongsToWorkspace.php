<?php

namespace App\Models\Concerns;

use App\Tenancy\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToWorkspace
{
    protected static function bootBelongsToWorkspace(): void
    {
        static::addGlobalScope('workspace', function (Builder $builder): void {
            $workspaceId = app(WorkspaceContext::class)->id();
            if ($workspaceId !== null) {
                $builder->where($builder->getModel()->qualifyColumn('workspace_id'), $workspaceId);
            }
        });

        static::creating(function (Model $model): void {
            if (! $model->getAttribute('workspace_id')) {
                $model->setAttribute('workspace_id', app(WorkspaceContext::class)->id());
            }
        });
    }
}
