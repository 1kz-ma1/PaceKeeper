<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanAvailabilityRule extends Model
{
    protected $fillable = ['plan_id', 'day_of_week', 'available_minutes', 'is_optional'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'available_minutes' => 'integer', 'is_optional' => 'boolean'];
    }

    public function plan() { return $this->belongsTo(Plan::class); }
}
