@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Manage Chores</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6">Create Ad-hoc Chore</h2>
            <form method="POST" action="{{ route('chore-instances.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-4">
                    <input class="form-control form-control-sm" name="title" placeholder="Title" required>
                </div>
                <div class="col-12 col-md-2">
                    <input class="form-control form-control-sm" name="base_points" type="number" min="1" placeholder="Points" required>
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" name="assigned_to_user_id">
                        <option value="">Open chore</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <input class="form-control form-control-sm" name="due_at" type="datetime-local">
                </div>
                <div class="col-12">
                    <textarea class="form-control form-control-sm" name="description" placeholder="Description"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary btn-sm" type="submit">Create Chore</button>
                </div>
            </form>
        </div>
    </div>

    <div class="vstack gap-3">
        @forelse ($instances as $instance)
            <x-chore-card :chore="$instance">
                <form method="POST" action="{{ route('chore-instances.assign', $instance) }}" class="d-flex flex-wrap gap-2 align-items-center">
                    @csrf
                    <label class="small text-body-secondary">Reassign:</label>
                    <select class="form-select form-select-sm w-auto" name="assigned_to_user_id">
                        <option value="">Open</option>
                        @foreach ($householdUsers as $user)
                            <option value="{{ $user->id }}" @selected($instance->assigned_to_user_id === $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Save</button>
                </form>
            </x-chore-card>
        @empty
            <div class="alert alert-info mb-0">No chores found.</div>
        @endforelse
    </div>
@endsection
