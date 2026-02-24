@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Manage Chores</h1>
        <a href="{{ route('parent.chores.create') }}" class="btn btn-primary btn-sm">Add Chore</a>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Type</th>
                        <th scope="col">Points</th>
                        <th scope="col">Details</th>
                        <th scope="col">Assignee</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($templates as $template)
                        @php($currentClaim = $template->weeklyClaims->first())
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $template->title }}</div>
                                <div class="small text-body-secondary">{{ $template->description ?: 'No description.' }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-primary">Recurring chore</span>
                            </td>
                            <td>{{ $template->points }}</td>
                            <td class="small text-body-secondary">
                                {{ $template->recurrence_type->value }}{{ $template->recurrence_interval ? ' · every ' . $template->recurrence_interval . ' days' : '' }}
                                <div>{{ $template->is_active ? 'Active' : 'Archived' }}</div>
                            </td>
                            <td>{{ $currentClaim?->assignedTo?->name ?: ($template->defaultAssignee?->name ?: 'Open') }}</td>
                            <td class="text-end">
                                <a href="{{ route('parent.chores.recurring.edit', $template) }}" class="btn btn-outline-dark btn-sm">Edit</a>
                            </td>
                        </tr>
                    @empty
                    @endforelse

                    @foreach ($instances as $instance)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $instance->title }}</div>
                                <div class="small text-body-secondary">{{ $instance->description ?: 'No description.' }}</div>
                            </td>
                            <td>
                                <span class="badge text-bg-secondary">One-time chore</span>
                            </td>
                            <td>{{ $instance->base_points }}</td>
                            <td class="small text-body-secondary">
                                {{ $instance->status->value }}
                                <div>{{ $instance->due_at?->format('M j, Y') ?: 'No due date' }}</div>
                            </td>
                            <td>{{ $instance->assignee?->name ?: 'Open' }}</td>
                            <td class="text-end">
                                <a href="{{ route('parent.chores.one-time.edit', $instance) }}" class="btn btn-outline-dark btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($templates->isEmpty() && $instances->isEmpty())
                        <tr>
                            <td colspan="6" class="text-center py-4 text-body-secondary">No chores yet. Click "Add Chore" to create one.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
@endsection
