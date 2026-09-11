<nav class="mobile-tabbar" aria-label="モバイルナビゲーション">
    <a href="{{ route('home') }}"
       class="mobile-tabbar-link {{ request()->routeIs('home') || request()->routeIs('calendar.*') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('home') || request()->routeIs('calendar.*') || request()->routeIs('my_plans.*') || request()->routeIs('plans.show') || request()->routeIs('plans.edit') || request()->routeIs('tasks.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z"/></svg>
        <span>ホーム</span>
    </a>

    <a href="{{ route('navigation.index') }}"
       data-navigation-link
       data-onboarding-target="today-nav"
       class="mobile-tabbar-link mobile-tabbar-primary {{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('navigation.*') || request()->routeIs('work_sessions.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M13.2 2 5.5 13h5.2L9.9 22 18.5 10h-5.3V2Z"/></svg>
        <span>今日</span>
    </a>

    <a href="{{ route('roadmap.index') }}"
       data-onboarding-target="roadmap-nav"
       class="mobile-tabbar-link {{ request()->routeIs('roadmap.*') || request()->routeIs('chat.*') || request()->routeIs('achievements.*') || request()->routeIs('plans.review_assistant.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('roadmap.*') || request()->routeIs('chat.*') || request()->routeIs('achievements.*') || request()->routeIs('plans.review_assistant.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5.5 9 3l6 2.5L20 3v15.5L15 21l-6-2.5L4 21V5.5Zm5-2.5v15.5M15 5.5V21"/></svg>
        <span>マップ</span>
    </a>

    <a href="{{ route('timeline.index') }}"
       class="mobile-tabbar-link {{ request()->routeIs('timeline.*') ? 'is-active' : '' }}"
       aria-current="{{ request()->routeIs('timeline.*') ? 'page' : 'false' }}">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5l3 2M5.4 5.4A9 9 0 1 1 3 12H1l3-3 3 3H5a7 7 0 1 0 2.05-4.95"/></svg>
        <span>履歴</span>
    </a>
</nav>
