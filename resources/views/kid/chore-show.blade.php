@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Chore Details</h1>
        <a class="btn btn-outline-secondary btn-sm" href="{{ route('kid.chores') }}">Back</a>
    </div>

    <x-chore-card :chore="$choreInstance" class="mb-3" />

    <div class="row g-3">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    @php($canClaim = auth()->user()?->can('claim', $choreInstance) ?? false)
                    <h2 class="h6">Claim</h2>
                    <p class="text-body-secondary small">Claim this chore if it is open.</p>
                    <form method="POST" action="{{ route('chore-instances.claim', $choreInstance) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" @disabled(! $canClaim)>Claim Chore</button>
                    </form>
                    @unless($canClaim)
                        <div class="small text-body-secondary mt-2">This chore can be claimed only when it is open and due.</div>
                    @endunless
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6">Submit Completion</h2>
                    <p class="text-body-secondary small mb-3">Add helpers if anyone worked with you.</p>
                    <form method="POST" action="{{ route('chore-completions.store') }}" id="completion-form">
                        @csrf
                        <input type="hidden" name="chore_instance_id" value="{{ $choreInstance->id }}">
                        <div class="mb-3">
                            <div class="small fw-semibold mb-2">Helpers</div>
                            @forelse ($helpers as $helper)
                                <div class="form-check">
                                    <input class="form-check-input helper-checkbox" type="checkbox" value="{{ $helper->id }}" id="helper-{{ $helper->id }}" name="helper_user_ids[]">
                                    <label class="form-check-label" for="helper-{{ $helper->id }}">{{ $helper->name }}</label>
                                </div>
                            @empty
                                <div class="text-body-secondary small">No helper options available.</div>
                            @endforelse
                        </div>
                        <div class="alert alert-warning py-2 px-3 small d-none" id="helper-warning">
                            Participants currently exceed available points. Only the primary doer may receive points.
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">Submit for Approval</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const warningEl = document.getElementById('helper-warning');
            const checkboxes = document.querySelectorAll('.helper-checkbox');
            const basePoints = Number({{ (int) $choreInstance->base_points }});

            const updateWarning = () => {
                const selectedHelpers = Array.from(checkboxes).filter((input) => input.checked).length;
                const participants = selectedHelpers + 1;
                const shouldWarn = participants > basePoints;
                warningEl.classList.toggle('d-none', !shouldWarn);
            };

            checkboxes.forEach((input) => input.addEventListener('change', updateWarning));
            updateWarning();
        })();
    </script>
@endpush
