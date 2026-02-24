@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Add Chore</h1>
        <a href="{{ route('parent.chores') }}" class="btn btn-outline-secondary btn-sm">Back to Manage Chores</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label for="chore-kind" class="form-label">Chore Type</label>
                <select id="chore-kind" class="form-select">
                    <option value="recurring">Recurring chore</option>
                    <option value="one_time">One-time chore</option>
                </select>
                <div class="form-text">Pick a type to show the right options.</div>
            </div>

            <form id="recurring-form" method="POST" action="{{ route('chore-templates.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label" for="recurring-title">Title</label>
                    <input id="recurring-title" class="form-control" name="title" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="recurring-points">Points</label>
                    <input id="recurring-points" class="form-control" name="points" type="number" min="1" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="recurrence-type">Recurrence</label>
                    <select id="recurrence-type" class="form-select" name="recurrence_type" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="weekdays">Weekdays</option>
                        <option value="every_n_days">Every N Days</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 d-none" data-recurring-interval-wrap>
                    <label class="form-label" for="recurring-interval">Interval (days)</label>
                    <input id="recurring-interval" class="form-control" name="recurrence_interval" type="number" min="1" max="365">
                </div>
                <div class="col-12 col-md-6 d-none" data-recurring-weekdays-wrap>
                    <label class="form-label d-block">Weekdays</label>
                    @foreach ([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 0 => 'Sun'] as $dayValue => $dayLabel)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="weekday-{{ $dayValue }}" name="recurrence_weekdays[]" value="{{ $dayValue }}">
                            <label class="form-check-label" for="weekday-{{ $dayValue }}">{{ $dayLabel }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="recurring-assignee">Default Assignee</label>
                    <select id="recurring-assignee" class="form-select" name="default_assignee_user_id">
                        <option value="">Open chore</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="recurring-description">Description</label>
                    <textarea id="recurring-description" class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Add Chore</button>
                </div>
            </form>

            <form id="one-time-form" method="POST" action="{{ route('chore-instances.store') }}" class="row g-3 d-none">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label" for="one-time-title">Title</label>
                    <input id="one-time-title" class="form-control" name="title">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="one-time-points">Points</label>
                    <input id="one-time-points" class="form-control" name="base_points" type="number" min="1">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="one-time-due-at">Due Date</label>
                    <input id="one-time-due-at" class="form-control" name="due_at" type="date">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="one-time-assignee">Assignee</label>
                    <select id="one-time-assignee" class="form-select" name="assigned_to_user_id">
                        <option value="">Open chore</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="one-time-description">Description</label>
                    <textarea id="one-time-description" class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Add Chore</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var typeSelect = document.getElementById('chore-kind');
            var recurringForm = document.getElementById('recurring-form');
            var oneTimeForm = document.getElementById('one-time-form');
            var recurringType = document.getElementById('recurrence-type');
            var intervalWrap = document.querySelector('[data-recurring-interval-wrap]');
            var weekdaysWrap = document.querySelector('[data-recurring-weekdays-wrap]');
            var recurringIntervalInput = document.getElementById('recurring-interval');
            var oneTimeTitle = document.getElementById('one-time-title');
            var oneTimePoints = document.getElementById('one-time-points');
            var recurringTitle = document.getElementById('recurring-title');
            var recurringPoints = document.getElementById('recurring-points');

            function setFormType(type) {
                var isRecurring = type === 'recurring';
                recurringForm.classList.toggle('d-none', !isRecurring);
                oneTimeForm.classList.toggle('d-none', isRecurring);

                recurringTitle.required = isRecurring;
                recurringPoints.required = isRecurring;
                recurringType.required = isRecurring;

                oneTimeTitle.required = !isRecurring;
                oneTimePoints.required = !isRecurring;
            }

            function updateRecurringOptions() {
                var recurrence = recurringType.value;
                var showInterval = recurrence === 'every_n_days';
                var showWeekdays = recurrence === 'weekly';

                intervalWrap.classList.toggle('d-none', !showInterval);
                weekdaysWrap.classList.toggle('d-none', !showWeekdays);
                recurringIntervalInput.required = showInterval;
            }

            typeSelect.addEventListener('change', function () {
                setFormType(typeSelect.value);
            });
            recurringType.addEventListener('change', updateRecurringOptions);

            setFormType(typeSelect.value);
            updateRecurringOptions();
        })();
    </script>
@endsection
