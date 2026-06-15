<!-- Collapsible Menu -->
<div class="collapse d-lg-block settings-nav" id="settingsNav">
    <div class="card-settings border-bottom">
        <div class="list-group list-group-sm list-group-flush">
            @foreach($availableSettings as $route => $setting)
                <a href="{{ route('my.settings', ['type' => $route]) }}" class="list-group-item list-group-item-action d-flex justify-content-between {{ $activeSettingsTab == $route ? 'active' : '' }}">
                    <div class="d-flex align-items-center">
                        @include('elements.icon', ['icon' => $setting['icon'].'-outline', 'centered' => 'false', 'classes' => 'mr-3', 'variant' => 'medium'])
                        <span>{{ ucfirst(__(str_replace('-', ' ', $route))) }}</span>
                    </div>
                    <div class="d-flex align-items-center">
                        @include('elements.icon', ['icon' => 'chevron-forward-outline'])
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</div>
