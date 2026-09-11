@extends('layouts.app')

@section('title', 'AIタスク作成支援')

@section('content')
    <div class="mx-auto max-w-5xl space-y-8">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 font-heading">
                    AIで初期計画を生成
                </h1>
                <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600">
                    計画内容をもとにAIへ相談するためのプロンプトを生成し、JSON 2.0からタスクと実行順を登録できます。
                    API連携は行わず、任意のAIサービスを利用しやすくするための補助機能です。
                </p>
            </div>

            <a href="{{ route('plans.show', $plan) }}" class="btn-secondary">
                計画詳細へ戻る
            </a>
        </div>

        <section class="info-card">
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 font-heading">
                        対象計画
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $plan->title }}
                    </p>
                </div>
            </div>
        </section>

        <section class="info-card space-y-4">
            <div>
                <h2 class="text-xl font-bold text-slate-900 font-heading">
                    1. AIに貼り付けるプロンプト
                </h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    以下の内容をコピーして、ChatGPT、Gemini、ClaudeなどのAIに貼り付けてください。
                    AIが出力した内容（JSON）を貼り付けることで、タスクを一括作成できます。
                    ※ 情報が不足している場合、AIは先に質問を行います。
                    質問に回答したうえで、最終的にPace Keeperで読み込めるJSON形式のタスク案を出力してもらいます。
                </p>
            </div>

            <textarea
                id="aiPrompt"
                class="form-control min-h-[360px] font-mono text-sm"
                readonly
            >{{ $prompt }}</textarea>

            <div class="flex flex-wrap gap-3">
                <button type="button" class="btn-primary" onclick="copyAiPrompt()" data-onboarding-target="ai-copy">
                    プロンプトをコピー
                </button>
            </div>
        </section>

        <section class="info-card space-y-4" data-onboarding-target="ai-import">
            <div>
                <h2 class="text-xl font-bold text-slate-900 font-heading">
                    2. AI出力JSONから初期計画を作成
                </h2>
                <p class="mt-2 text-sm leading-7 text-slate-600">
                    AIが出力したJSONを貼り付けてください。
                    正しい形式であれば、この計画にタスクを一括登録します。
                </p>
            </div>

            @if ($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <p class="font-semibold">入力内容を確認してください。</p>
                    <ul class="mt-2 list-disc space-y-1 pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('plans.ai_task_assistant.import', $plan) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="tasks_json" class="mb-2 block text-sm font-semibold text-slate-700">
                        AIが出力したJSON
                    </label>
                    <textarea
                        id="tasks_json"
                        name="tasks_json"
                        class="form-control min-h-[320px] font-mono text-sm"
                        placeholder='{"schema_version":"2.0","flow":"plan_generation","target_plan":{"id":{{ $plan->id }},"title":"{{ $plan->title }}"},"summary":"初期計画","operations":[{"type":"add_task","client_ref":"task_1","title":"タスク名","description":"完了条件","estimated_minutes":120,"remaining_minutes":120,"priority":1,"progress_percent":0,"status":"todo"},{"type":"reorder_tasks","items":[{"task_ref":"task_1"}]}]}'
                    >{{ old('tasks_json') }}</textarea>
                </div>

                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-7 text-amber-800">
                    <p class="font-semibold">登録前の確認について</p>
                    <p>
                        対象計画、操作種別、数値範囲、タスク参照を検証してから一括登録します。
                        AIが生成した内容に違和感がある場合は、貼り付け前にJSONを修正してください。
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn-primary">
                        タスクを一括作成
                    </button>

                    <a href="{{ route('plans.show', $plan) }}" class="btn-secondary">
                        キャンセル
                    </a>
                </div>
            </form>
        </section>
    </div>

    <script>
        function copyAiPrompt() {
            const prompt = document.getElementById('aiPrompt');

            prompt.select();
            prompt.setSelectionRange(0, 99999);

            navigator.clipboard.writeText(prompt.value)
                .then(() => {
                    alert('プロンプトをコピーしました。');
                })
                .catch(() => {
                    alert('コピーに失敗しました。手動で選択してコピーしてください。');
                });
        }
    </script>
@endsection
