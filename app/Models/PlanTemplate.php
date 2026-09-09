<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTemplate extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'estimated_days',
        'is_official',
    ];

    public function templateTasks()
    {
        return $this->hasMany(TemplateTask::class);
    }
}