<?php

namespace App\Http\Controllers;

use App\Models\PlanTemplate;
use App\Models\TemplateTask;
use Illuminate\Http\Request;

class AdminTemplateController extends Controller
{
    public function login()
    {
        return view('admin.templates.login');
    }

    public function authenticate(Request $request)
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if ($validated['password'] !== env('TEMPLATE_ADMIN_PASSWORD')) {
            return back()->withErrors([
                'password' => '管理用パスワードが正しくありません。',
            ]);
        }

        session(['template_admin_authenticated' => true]);

        return redirect()->route('admin.templates.create');
    }

    public function create()
    {
        if (! session('template_admin_authenticated')) {
            return redirect()->route('admin.templates.login');
        }

        return view('admin.templates.create');
    }

    public function store(Request $request)
    {
        if (! session('template_admin_authenticated')) {
            abort(403);
        }

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'estimated_days' => ['required', 'integer', 'min:0'],
            'tasks' => ['required', 'array'],
            'tasks.*.title' => ['required', 'string', 'max:255'],
            'tasks.*.description' => ['nullable', 'string'],
            'tasks.*.estimated_minutes' => ['required', 'integer', 'min:0'],
        ]);

        $template = PlanTemplate::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'] ?? null,
            'estimated_days' => $validated['estimated_days'],
            'is_official' => true,
        ]);

        foreach ($validated['tasks'] as $index => $task) {
            TemplateTask::create([
                'plan_template_id' => $template->id,
                'title' => $task['title'],
                'description' => $task['description'] ?? null,
                'estimated_minutes' => $task['estimated_minutes'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('templates.show', $template);
    }
}