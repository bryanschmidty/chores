<nav class="navbar navbar-expand-lg bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="{{ route('app.home') }}">Chores</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main-nav" aria-controls="main-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="main-nav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                @if (auth()->user()?->hasAnyRole(['kid', 'parent', 'supervisor']))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('kid.index') }}">My List</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('kid.chores') }}">All Chores</a>
                    </li>
                @endif
                @if (auth()->user()?->hasRole('parent'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent.templates') }}">Templates</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent.chores') }}">Manage Chores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent.history') }}">History</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('parent.leaderboard') }}">Leaderboard</a>
                    </li>
                @endif
                @if (auth()->user()?->hasRole('supervisor'))
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('supervisor.queue') }}">Approval Queue</a>
                    </li>
                @endif
            </ul>
            <div class="d-flex align-items-center gap-2">
                @auth
                    <span class="text-secondary small">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Log Out</button>
                    </form>
                @else
                    <a class="btn btn-primary btn-sm" href="{{ route('auth.google.redirect') }}">Log In with Google</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
