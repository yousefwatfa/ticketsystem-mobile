<div class="menu-item me-lg-1">
    <a class="menu-link py-3 {{ request()->routeIs('dashboard') || request()->routeIs('tickets.index') ? 'active' : '' }}" href="{{ route('dashboard') }}">
        <span class="menu-title">الرئيسية</span>
    </a>
</div>

<div class="menu-item me-lg-1 btn-create-ticket">
    <a class="menu-link py-3 {{ request()->routeIs('tickets.create') ? 'active' : '' }}" href="{{ route('tickets.create') }}">

        <span class="menu-title">تذكرة جديدة</span>
    </a>
</div>

@if(Auth::user()->role?->name == 'Admin' || Auth::user()->is_root)
    <div class="menu-item me-lg-1">
        <a class="menu-link py-3 {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" href="{{ route('admin.tickets.index') }}">

            <span class="menu-title">التذاكر</span>
        </a>
    </div>

    @can('is_root')
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                <span class="menu-title">المشاكل</span>
            </a>
        </div>
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}" href="{{ route('admin.departments.index') }}">
                <span class="menu-title">الأقسام</span>
            </a>
        </div>
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                <span class="menu-title">المستخدمين</span>
            </a>
        </div>
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ request()->routeIs('admin.structure.*') ? 'active' : '' }}" href="{{ route('admin.structure.index') }}">
                <span class="menu-title">الهيكلية</span>
            </a>
        </div>
    @endcan

    @if(Auth::user()->is_root || Auth::user()->can_access_finished_tickets)
        <div class="menu-item me-lg-1">
            <a class="menu-link py-3 {{ request()->routeIs('admin.finished_tickets.*') ? 'active' : '' }}" href="{{ route('admin.finished_tickets.create') }}">
                <span class="menu-title">مهمة منتهية</span>
            </a>
        </div>
    @endif

    <div class="menu-item me-lg-1">
        <a class="menu-link py-3 {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}">
            <span class="menu-title">التقارير</span>
        </a>
    </div>
@endif

@if(Auth::user()->role?->name == 'Viewer')
    <div class="menu-item me-lg-1">
        <a class="menu-link py-3 {{ request()->routeIs('viewer.*') ? 'active' : '' }}" href="{{ route('viewer.dashboard') }}">
            <span class="menu-title">التذاكر</span>
        </a>
    </div>
@endif

<div class="menu-item me-lg-1">
    <a class="menu-link py-3 {{ request()->routeIs('profile.index') ? 'active' : '' }}" href="{{ route('profile.index') }}">
        <span class="menu-title">ملف الشخصي</span>
    </a>
</div>
