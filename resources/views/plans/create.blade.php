@extends('layouts.app')

@section('title', '計画作成 | Pace Keeper')

@section('content')
    <section class="mb-8">
        <p class="mb-2 text-sm font-semibold text-slate-500">Create Plan</p>

        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            計画作成
        </h1>

        <p class="mt-3 max-w-3xl leading-7 text-slate-600">
            目標・開始日・期限・カテゴリなどを入力して、新しい計画を作成します。
            作成後はタスクや作業ログを追加し、必要作業ペースを確認できます。
        </p>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <h2 class="mb-2 font-bold">入力内容を確認してください</h2>

                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('plans.store') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label for="title" class="mb-2 block text-sm font-medium text-slate-700">
                        計画タイトル
                    </label>

                    <input
                        id="title"
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="例：応用情報処理技術者試験 合格"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    >
                </div>

                <div>
                    <label for="description" class="mb-2 block text-sm font-medium text-slate-700">
                        説明
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="5"
                        placeholder="この計画の目的や概要"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    >{{ old('description') }}</textarea>
                </div>

                <div>
                    <label for="category" class="mb-2 block text-sm font-medium text-slate-700">
                        カテゴリ
                    </label>

                    <select
                        id="category"
                        name="category"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                    >
                        <option value="">選択してください</option>
                        <option value="資格学習" @selected(old('category') === '資格学習')>資格学習</option>
                        <option value="個人開発" @selected(old('category') === '個人開発')>個人開発</option>
                        <option value="制作活動" @selected(old('category') === '制作活動')>制作活動</option>
                        <option value="ゲーム開発" @selected(old('category') === 'ゲーム開発')>ゲーム開発</option>
                        <option value="その他" @selected(old('category') === 'その他')>その他</option>
                    </select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="start_date" class="mb-2 block text-sm font-medium text-slate-700">
                            開始日
                        </label>

                        <input
                            id="start_date"
                            type="date"
                            name="start_date"
                            value="{{ old('start_date') }}"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                        >
                    </div>

                    <div>
                        <label for="deadline" class="mb-2 block text-sm font-medium text-slate-700">
                            期限
                        </label>

                        <input
                            id="deadline"
                            type="date"
                            name="deadline"
                            value="{{ old('deadline') }}"
                            required
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 outline-none focus:border-slate-900 focus:ring-2 focus:ring-slate-200"
                        >
                    </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <label class="flex items-start gap-3">
                        <input
                            type="checkbox"
                            name="is_public"
                            value="1"
                            @checked(old('is_public'))
                            class="mt-1"
                        >

                        <span>
                            <span class="block font-medium text-slate-900">
                                この計画を公開する
                            </span>

                            <span class="mt-1 block text-sm leading-6 text-slate-600">
                                公開すると、他の人が公開計画一覧や共有URLからこの計画を閲覧できるようになります。
                                公開ページでは編集や作業ログの追加はできません。
                            </span>
                        </span>
                    </label>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="submit"
                        class="btn-primary"
                    >
                        計画を作成する
                    </button>

                    <a
                        href="{{ route('home') }}"
                        class="btn-secondary"
                    >
                        ホームへ戻る
                    </a>
                </div>
            </form>
        </div>

        <aside class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-bold text-slate-900">
                作成後にできること
            </h2>

            <ul class="mt-4 space-y-3 text-sm leading-6 text-slate-600">
                <li class="rounded-lg bg-slate-50 p-3">
                    タスクを追加し、想定作業時間と進捗率を管理できます。
                </li>

                <li class="rounded-lg bg-slate-50 p-3">
                    作業ログを記録すると、実績時間と進捗が反映されます。
                </li>

                <li class="rounded-lg bg-slate-50 p-3">
                    期限までに必要な1日あたりの作業時間を確認できます。
                </li>

                <li class="rounded-lg bg-slate-50 p-3">
                    公開設定をONにすると、公開計画として他の人に共有できます。
                </li>
            </ul>

            <div class="mt-6 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm leading-6 text-sky-900">
                計画を作成した後、「AIで初期計画を生成」から、あなたの現在地に合うタスク構成を作れます。
            </div>
        </aside>
    </section>
@endsection
