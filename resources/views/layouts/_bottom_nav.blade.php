{{-- Mobile bottom navigation (hidden on large screens, where the top menu is used) --}}
@php
    $navUser = Auth::user();
    $isAdminNav = $navUser->role?->name === 'Admin' || $navUser->is_root;
    $isViewerNav = $navUser->role?->name === 'Viewer';
@endphp
<nav class="mobile-bottom-nav d-lg-none" aria-label="التنقل الرئيسي">
    <a href="{{ route('dashboard') }}"
       class="mobile-bottom-nav__item {{ request()->routeIs('dashboard', 'tickets.index', 'tickets.show') ? 'is-active' : '' }}"
       @if(request()->routeIs('dashboard', 'tickets.index')) aria-current="page" @endif>
        <i class="bi bi-house-door" aria-hidden="true"></i>
        <span>الرئيسية</span>
    </a>

    @if($isAdminNav)
        <a href="{{ route('admin.tickets.index') }}"
           class="mobile-bottom-nav__item {{ request()->routeIs('admin.tickets.*') ? 'is-active' : '' }}"
           @if(request()->routeIs('admin.tickets.index')) aria-current="page" @endif>
            <i class="bi bi-inbox" aria-hidden="true"></i>
            <span>التذاكر</span>
        </a>
    @elseif($isViewerNav)
        <a href="{{ route('viewer.dashboard') }}"
           class="mobile-bottom-nav__item {{ request()->routeIs('viewer.*') ? 'is-active' : '' }}"
           @if(request()->routeIs('viewer.*')) aria-current="page" @endif>
            <i class="bi bi-inbox" aria-hidden="true"></i>
            <span>التذاكر</span>
        </a>
    @endif

    <a href="{{ route('tickets.create') }}"
       class="mobile-bottom-nav__item mobile-bottom-nav__item--primary btn-create-ticket {{ request()->routeIs('tickets.create') ? 'is-active' : '' }}"
       @if(request()->routeIs('tickets.create')) aria-current="page" @endif>
        <span class="mobile-bottom-nav__fab"><i class="bi bi-plus-lg" aria-hidden="true"></i></span>
        <span>تذكرة جديدة</span>
    </a>

    <a href="{{ route('profile.index') }}"
       class="mobile-bottom-nav__item {{ request()->routeIs('profile.*') ? 'is-active' : '' }}"
       @if(request()->routeIs('profile.*')) aria-current="page" @endif>
        <i class="bi bi-person" aria-hidden="true"></i>
        <span>حسابي</span>
    </a>
</nav>
