<nav class="mobile-tabbar" aria-label="モバイルナビゲーション">
    <a href="{{ route('chat.index') }}"
       class="mobile-tabbar-link {{ request()->routeIs('chat.*') || request()->routeIs('plans.review_assistant.*') || request()->routeIs('achievements.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('chat.*') || request()->routeIs('plans.review_assistant.*') || request()->routeIs('achievements.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.7 4v-4.25A2.5 2.5 0 0 1 4 13.5v-8Z"/></svg>
        <span>計画</span>
    </a>

    <a href="{{ route('navigation.index') }}"
       data-navigation-link
       class="mobile-tabbar-link mobile-tabbar-primary {{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 2 5.5 13h5.2L9.9 22 18.5 10h-5.3V2Z"/></svg>
        <span>今日</span>
    </a>

    <a href="{{ route('home') }}"
       class="mobile-tabbar-link {{ request()->routeIs('home') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('home') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg>
        <span>ホーム</span>
    </a>

    <a href="{{ route('public_plans.index') }}"
       class="mobile-tabbar-link {{ request()->routeIs('public_plans.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('public_plans.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0 0c2.2-2.35 3.3-5.35 3.3-9S14.2 5.35 12 3m0 18c-2.2-2.35-3.3-5.35-3.3-9S9.8 5.35 12 3M3.5 9h17m-17 6h17"/></svg>
        <span>公開</span>
    </a>
</nav>
