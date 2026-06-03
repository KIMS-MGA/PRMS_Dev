<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordSchedule extends Model
{
    protected $fillable = ['record_id', 'stage_id', 'scheduled_by', 'scheduled_at', 'notes', 'action'];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function record()
    {
        return $this->belongsTo(Record::class);
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_id');
    }

    public function scheduler()
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
