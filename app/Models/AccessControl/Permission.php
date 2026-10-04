<?php

namespace App\Models\AccessControl;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'label',
    ];
}
