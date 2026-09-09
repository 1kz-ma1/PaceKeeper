<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateTask extends Model
{
    protected $fillable = [
        'plan_template_id',
        'title',
        'description',
        'estimated_minutes',
        'sort_order',
    ];

    public function planTemplate()
    {
        return $this->belongsTo(PlanTemplate::class);
    }
}