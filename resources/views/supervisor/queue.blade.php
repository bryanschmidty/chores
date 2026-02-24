@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Pending Approvals</h1>

    <div class="vstack gap-3">
        @forelse ($pendingCompletions as $completion)
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h2 class="h6 mb-1">{{ $completion->choreInstance?->title ?? 'Deleted Chore' }}</h2>
                            <p class="small text-body-secondary mb-0">
                                Submitted by {{ $completion->completedBy?->name ?? 'Unknown' }} ·
                                Base points {{ $completion->choreInstance?->base_points ?? 0 }}
                            </p>
                        </div>
                        <x-status-badge :status="$completion->approval_status" />
                    </div>

                    <div class="small text-body-secondary mb-3">
                        Helpers:
                        @foreach ($completion->participants as $participant)
                            <span class="me-2">{{ $participant->user?->name }}</span>
                        @endforeach
                    </div>

                    <div class="row g-2">
                        <div class="col-12 col-lg-6">
                            <form method="POST" action="{{ route('chore-completions.approve', $completion) }}" class="card card-body bg-light">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Adjusted points (optional)</label>
                                    <input class="form-control form-control-sm" name="supervisor_adjusted_points" type="number" min="0">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Comment (optional)</label>
                                    <textarea class="form-control form-control-sm" name="approval_comment"></textarea>
                                </div>
                                <button class="btn btn-success btn-sm" type="submit">Approve</button>
                            </form>
                        </div>
                        <div class="col-12 col-lg-6">
                            <form method="POST" action="{{ route('chore-completions.reject', $completion) }}" class="card card-body bg-light">
                                @csrf
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Rejection reason</label>
                                    <textarea class="form-control form-control-sm" name="rejection_reason" required></textarea>
                                </div>
                                <button class="btn btn-outline-danger btn-sm" type="submit">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info mb-0">No pending approvals.</div>
        @endforelse
    </div>
@endsection
