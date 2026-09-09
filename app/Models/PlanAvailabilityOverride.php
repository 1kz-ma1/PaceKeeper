<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAvailabilityOverride extends Model
{
    protected $fillable = ['plan_id', 'date', 'available_minutes', 'note'];

    protected function casts(): array
    {
        return ['date' => 'date', 'available_minutes' => 'integer'];
    }

    public function plan() { return $this->belongsTo(Plan::class); }
}
