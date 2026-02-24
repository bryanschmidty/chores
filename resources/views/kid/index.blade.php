@extends('layouts.app')

@section('content')
    @php
        $filterLabel = match ($filter) {
            'my_weekly_chores' => 'My Weekly Chores',
            'my_completed_chores' => 'My Completed Chores',
            'unclaimed' => 'Unclaimed',
            default => 'My Chores',
        };
    @endphp

    <div class="row row-cols-2 g-2 mb-3">
        <div class="col">
            <a href="{{ route('kid.index', ['filter' => 'unclaimed']) }}" class="text-decoration-none text-reset d-block h-100">
                <div class="card shadow-sm h-100 app-stat-card">
                    <div class="card-body py-2 px-3 text-center">
                        <div class="fs-3 fw-bold lh-1 stat-number">{{ $unclaimedChoreCount }}</div>
                        <div class="small text-body-secondary mt-1">Unclaimed Chores</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col">
            <div class="card shadow-sm h-100 app-stat-card">
                <div class="card-body py-2 px-3 text-center">
                    <div class="fs-3 fw-bold lh-1 stat-number">{{ $myPoints }}</div>
                    <div class="small text-body-secondary mt-1">My Points</div>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <button type="button" class="btn btn-outline-secondary w-100 d-flex justify-content-between align-items-center app-surface" id="kid-filter-toggle" aria-expanded="false" aria-controls="kid-filter-options">
            <span class="fw-semibold">{{ $filterLabel }}</span>
            <span class="small">v</span>
        </button>
        <div id="kid-filter-options" class="card mt-2 d-none">
            <div class="list-group list-group-flush">
                <a href="{{ route('kid.index', ['filter' => 'my_chores']) }}" class="list-group-item list-group-item-action @if ($filter === 'my_chores') active @endif">My Chores</a>
                <a href="{{ route('kid.index', ['filter' => 'my_weekly_chores']) }}" class="list-group-item list-group-item-action @if ($filter === 'my_weekly_chores') active @endif">My Weekly Chores</a>
                <a href="{{ route('kid.index', ['filter' => 'my_completed_chores']) }}" class="list-group-item list-group-item-action @if ($filter === 'my_completed_chores') active @endif">My Completed Chores</a>
                <a href="{{ route('kid.index', ['filter' => 'unclaimed']) }}" class="list-group-item list-group-item-action @if ($filter === 'unclaimed') active @endif">Unclaimed</a>
            </div>
        </div>
    </div>

    @if ($filter === 'my_chores')
        <div class="vstack gap-3">
            @forelse ($myChores as $instance)
                <div class="card shadow-sm app-surface js-open-chore-card" data-open-url="{{ route('kid.chores.show', ['choreInstance' => $instance, 'filter' => 'my_chores']) }}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h3 class="h6 mb-0">{{ $instance->title }}</h3>
                            <span class="small text-body-secondary">{{ $instance->base_points }} pts</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="d-flex justify-content-center">
                    <div class="card border-info-subtle bg-info-subtle text-center w-100" style="max-width: 32rem;">
                        <div class="card-body py-4">
                            <a href="{{ route('kid.index', ['filter' => 'unclaimed']) }}" class="h5 d-inline-block mb-2 text-decoration-none">
                                No claimed chores
                            </a>
                            <p class="mb-0 text-body-secondary">Tap above to switch to unclaimed chores and claim one.</p>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    @if ($filter === 'my_weekly_chores')
        <div class="vstack gap-3">
            @forelse ($myWeeklyChores as $instance)
                <div class="card shadow-sm app-surface js-open-chore-card" data-open-url="{{ route('kid.chores.show', ['choreInstance' => $instance, 'filter' => 'my_weekly_chores']) }}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h3 class="h6 mb-0">{{ $instance->title }}</h3>
                            <span class="small text-body-secondary">{{ $instance->base_points }} pts</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-info-subtle bg-info-subtle">
                    <div class="card-body">
                        <h2 class="h6 mb-1">No weekly chores</h2>
                        <p class="mb-0 text-body-secondary">No chores are assigned to you for this week.</p>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    @if ($filter === 'my_completed_chores')
        <div class="vstack gap-3">
            @forelse ($myCompletedChores as $instance)
                <div class="card shadow-sm app-surface js-open-chore-card" data-open-url="{{ route('kid.chores.show', ['choreInstance' => $instance, 'filter' => 'my_completed_chores']) }}">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center gap-3">
                            <h3 class="h6 mb-0">{{ $instance->title }}</h3>
                            <div class="d-flex align-items-center gap-2">
                                <span class="small text-body-secondary">{{ $instance->base_points }} pts</span>
                                <span class="small {{ $instance->status->value === 'approved' ? 'text-success' : 'text-warning-emphasis' }}">
                                    {{ $instance->status->value === 'approved' ? 'Approved' : 'Pending Approval' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card border-info-subtle bg-info-subtle">
                    <div class="card-body">
                        <h2 class="h6 mb-1">No completed chores</h2>
                        <p class="mb-0 text-body-secondary">Completed chores will appear here after submission.</p>
                    </div>
                </div>
            @endforelse
        </div>
    @endif

    @if ($filter === 'unclaimed')
        <div class="vstack gap-3">
            @forelse ($unclaimedToday as $instance)
                <div class="card shadow-sm app-surface">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center gap-3 mb-2">
                            <h3 class="h6 mb-0">{{ $instance->title }}</h3>
                            <span class="small text-body-secondary">{{ $instance->base_points }} pts</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-sm btn-primary" href="{{ route('kid.chores.show', ['choreInstance' => $instance, 'filter' => 'unclaimed']) }}">Open</a>
                            <form method="POST" action="{{ route('chore-instances.claim', $instance) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">Claim</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info mb-0">No unclaimed chores due today.</div>
            @endforelse
        </div>
    @endif

    <script>
        (function () {
            var toggleButton = document.getElementById('kid-filter-toggle');
            var options = document.getElementById('kid-filter-options');
            var openCards = document.querySelectorAll('.js-open-chore-card');

            toggleButton.addEventListener('click', function () {
                var isHidden = options.classList.contains('d-none');
                options.classList.toggle('d-none', !isHidden);
                toggleButton.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            });

            openCards.forEach(function (card) {
                var startX = 0;
                var startY = 0;
                var moved = false;
                var scrollThreshold = 8;

                card.style.cursor = 'pointer';

                card.addEventListener('touchstart', function (event) {
                    var touch = event.touches[0];
                    startX = touch.clientX;
                    startY = touch.clientY;
                    moved = false;
                }, { passive: true });

                card.addEventListener('touchmove', function (event) {
                    var touch = event.touches[0];
                    if (Math.abs(touch.clientX - startX) > scrollThreshold || Math.abs(touch.clientY - startY) > scrollThreshold) {
                        moved = true;
                    }
                }, { passive: true });

                card.addEventListener('click', function () {
                    if (moved) {
                        return;
                    }

                    var url = card.getAttribute('data-open-url');
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        })();
    </script>
@endsection
