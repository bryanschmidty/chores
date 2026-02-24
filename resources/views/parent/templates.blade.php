@extends('layouts.app')

@section('content')
    <h1 class="h4 mb-3">Recurring Templates</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6">Create Template</h2>
            <form method="POST" action="{{ route('chore-templates.store') }}" class="row g-2">
                @csrf
                <div class="col-12 col-md-4">
                    <input class="form-control form-control-sm" name="title" placeholder="Title" required>
                </div>
                <div class="col-12 col-md-2">
                    <input class="form-control form-control-sm" name="points" type="number" min="1" placeholder="Points" required>
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" name="recurrence_type" required>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="weekdays">Weekdays</option>
                        <option value="every_n_days">Every N Days</option>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <input class="form-control form-control-sm" name="recurrence_interval" type="number" min="1" placeholder="Interval (optional)">
                </div>
                <div class="col-12">
                    <textarea class="form-control form-control-sm" name="description" placeholder="Description"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary btn-sm" type="submit">Create Template</button>
                </div>
            </form>
        </div>
    </div>

    <div class="vstack gap-3">
        @forelse ($templates as $template)
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h3 class="h6 mb-1">{{ $template->title }}</h3>
                            <p class="small text-body-secondary mb-2">{{ $template->description ?: 'No description.' }}</p>
                            <div class="small text-body-secondary">
                                {{ $template->recurrence_type->value }} · {{ $template->points }} points · {{ $template->is_active ? 'Active' : 'Archived' }}
                            </div>
                        </div>
                        <form method="POST" action="{{ route('chore-templates.archive', $template) }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ $template->is_active ? 'Archive' : 'Unarchive' }}</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="alert alert-info mb-0">No templates yet.</div>
        @endforelse
    </div>
@endsection
