@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Edit One-time Chore</h1>
        <a href="{{ route('parent.chores') }}" class="btn btn-outline-secondary btn-sm">Back to Manage Chores</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('chore-instances.update', $choreInstance) }}" class="row g-3">
                @csrf
                @method('PUT')

                <div class="col-12 col-md-6">
                    <label class="form-label" for="one-time-title">Title</label>
                    <input id="one-time-title" class="form-control" name="title" value="{{ old('title', $choreInstance->title) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="one-time-points">Points</label>
                    <input id="one-time-points" class="form-control" name="base_points" type="number" min="1" value="{{ old('base_points', $choreInstance->base_points) }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label" for="one-time-due-at">Due Date</label>
                    <input id="one-time-due-at" class="form-control" name="due_at" type="date" value="{{ old('due_at', $choreInstance->due_at?->format('Y-m-d')) }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="one-time-assignee">Assignee</label>
                    <select id="one-time-assignee" class="form-select" name="assigned_to_user_id">
                        <option value="">Open chore</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}" @selected((int) old('assigned_to_user_id', $choreInstance->assigned_to_user_id) === (int) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="one-time-description">Description</label>
                    <textarea id="one-time-description" class="form-control" name="description" rows="3">{{ old('description', $choreInstance->description) }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
