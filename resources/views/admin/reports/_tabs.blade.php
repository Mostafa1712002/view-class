@php($currentTab = $currentTab ?? '')
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'administrative' ? 'active' : '' }}" href="{{ route('admin.reports.administrative') }}">التقارير الإدارية</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'statistical' ? 'active' : '' }}" href="{{ route('admin.reports.statistical') }}">التقارير الإحصائية</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ $currentTab === 'user' ? 'active' : '' }}" href="{{ route('admin.reports.user-reports') }}">تقارير المستخدمين</a>
    </li>
</ul>
