<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}" href="{{ route('admin.subjects.index') }}">المواد</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('admin.subject-tracks.*') ? 'active' : '' }}" href="{{ route('admin.subject-tracks.index') }}">المسارات</a>
    </li>
</ul>
