@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">My Chores</h1>
            <p class="text-body-secondary mb-0">Assigned first, then open, then recurring in the next 7 days.</p>
        </div>
        <a class="btn btn-outline-primary btn-sm" href="{{ route('kid.chores') }}">View All</a>
    </div>

    <div class="vstack gap-3">
        @forelse ($instances as $instance)
            <x-chore-card :chore="$instance">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('kid.chores.show', $instance) }}">Open</a>
                    @if ($instance->assigned_to_user_id === null && in_array($instance->status->value, ['due', 'overdue'], true))
                        <form method="POST" action="{{ route('chore-instances.claim', $instance) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Claim</button>
                        </form>
                    @endif
                </div>
            </x-chore-card>
        @empty
            <div class="alert alert-info mb-0">No chores right now.</div>
        @endforelse
    </div>
@endsection
