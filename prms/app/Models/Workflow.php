<?php

namespace App\Models;

use App\Data\WorkflowConditions;
use Illuminate\Database\Eloquent\Model;

/**
 * @property WorkflowConditions $conditions_json
 */
class Workflow extends Model
{
    protected $fillable = ['module_id', 'name', 'trigger', 'conditions_json'];
    
    protected $casts = [
        'conditions_json' => 'array',
    ];

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class);
    }
}
