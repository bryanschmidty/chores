@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Edit Recurring Chore</h1>
        <a href="{{ route('parent.chores') }}" class="btn btn-outline-secondary btn-sm">Back to Manage Chores</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('chore-templates.update', $choreTemplate) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-md-6">
                    <label class="form-label" for="recurring-title">Title</label>
                    <input id="recurring-title" class="form-control" name="title" value="{{ old('title', $choreTemplate->title) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="recurring-points">Points</label>
                    <input id="recurring-points" class="form-control" name="points" type="number" min="1" value="{{ old('points', $choreTemplate->points) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="recurrence-type">Recurrence</label>
                    <select id="recurrence-type" class="form-select" name="recurrence_type" required>
                        <option value="daily" @selected(old('recurrence_type', $choreTemplate->recurrence_type->value) === 'daily')>Daily</option>
                        <option value="weekly" @selected(old('recurrence_type', $choreTemplate->recurrence_type->value) === 'weekly')>Weekly</option>
                        <option value="weekdays" @selected(old('recurrence_type', $choreTemplate->recurrence_type->value) === 'weekdays')>Weekdays</option>
                        <option value="every_n_days" @selected(old('recurrence_type', $choreTemplate->recurrence_type->value) === 'every_n_days')>Every N Days</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-none" data-recurring-interval-wrap>
                    <label class="form-label" for="recurring-interval">Interval (days)</label>
                    <input id="recurring-interval" class="form-control" name="recurrence_interval" type="number" min="1" max="365" value="{{ old('recurrence_interval', $choreTemplate->recurrence_interval) }}">
                </div>
                <div class="col-12 col-md-6 d-none" data-recurring-weekdays-wrap>
                    <label class="form-label d-block">Weekdays</label>
                    @php($selectedWeekdays = old('recurrence_weekdays', $choreTemplate->recurrence_weekdays ?? []))
                    @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $dayValue => $dayLabel)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="weekday-{{ $dayValue }}" name="recurrence_weekdays[]" value="{{ $dayValue }}" @checked(in_array($dayValue, array_map('intval', $selectedWeekdays), true))>
                            <label class="form-check-label" for="weekday-{{ $dayValue }}">{{ $dayLabel }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="recurring-assignee">Default Assignee</label>
                    <select id="recurring-assignee" class="form-select" name="default_assignee_user_id">
                        <option value="">Open chore</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}" @selected((int) old('default_assignee_user_id', $choreTemplate->default_assignee_user_id) === (int) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="current-week-claim-user">Claimed This Week</label>
                    <select id="current-week-claim-user" class="form-select" name="current_week_claim_user_id">
                        <option value="">Unclaimed</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}" @selected((int) old('current_week_claim_user_id', $currentWeekClaim?->assigned_to_user_id) === (int) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="recurring-description">Description</label>
                    <textarea id="recurring-description" class="form-control" name="description" rows="3">{{ old('description', $choreTemplate->description) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var recurringType = document.getElementById('recurrence-type');
            var intervalWrap = document.querySelector('[data-recurring-interval-wrap]');
            var weekdaysWrap = document.querySelector('[data-recurring-weekdays-wrap]');
            var recurringIntervalInput = document.getElementById('recurring-interval');

            function updateRecurringOptions() {
                var recurrence = recurringType.value;
                var showInterval = recurrence === 'every_n_days';
                var showWeekdays = recurrence === 'weekly';

                intervalWrap.classList.toggle('d-none', !showInterval);
                weekdaysWrap.classList.toggle('d-none', !showWeekdays);
                recurringIntervalInput.required = showInterval;
            }

            recurringType.addEventListener('change', updateRecurringOptions);
            updateRecurringOptions();
        })();
    </script>
@endsection
