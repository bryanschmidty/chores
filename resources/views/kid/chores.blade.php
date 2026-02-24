@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">All Chores</h1>
            <p class="text-body-secondary mb-0">Full household list with quick actions.</p>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('kid.index') }}">Back to My List</a>
    </div>

    <div class="vstack gap-3">
        @forelse ($instances as $instance)
            <x-chore-card :chore="$instance">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-primary" href="{{ route('kid.chores.show', $instance) }}">Details</a>
                    @if ($instance->assigned_to_user_id === null && in_array($instance->status->value, ['due', 'overdue'], true))
                        <form method="POST" action="{{ route('chore-instances.claim', $instance) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">Claim</button>
                        </form>
                    @endif
                </div>
            </x-chore-card>
        @empty
            <div class="alert alert-info mb-0">No chores available.</div>
        @endforelse
    </div>
@endsection
