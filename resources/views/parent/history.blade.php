@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Completion History</h1>

    <div class="vstack gap-3">
        @forelse ($history as $completion)
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="h6 mb-1">{{ $completion->choreInstance?->title ?? 'Deleted Chore' }}</h2>
                            <p class="small text-body-secondary mb-2">
                                Completed by {{ $completion->completedBy?->name ?? 'Unknown' }}
                                on {{ optional($completion->completed_at)?->format('M j, Y g:i A') }}
                            </p>
                        </div>
                        <x-status-badge :status="$completion->approval_status" />
                    </div>

                    <div class="small text-body-secondary">
                        Participants:
                        @foreach ($completion->participants as $participant)
                            <span class="me-2">{{ $participant->user?->name }} ({{ $participant->points_awarded ?? 0 }})</span>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info mb-0">No completion history yet.</div>
        @endforelse
    </div>
@endsection
