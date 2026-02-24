@props(['chore'])

<div {{ $attributes->merge(['class' => 'card shadow-sm']) }}>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h3 class="h6 mb-1">{{ $chore->title }}</h3>
                <p class="text-body-secondary mb-2">
                    {{ $chore->description ?: 'No description provided.' }}
                </p>
            </div>
            <x-status-badge :status="$chore->status" />
        </div>
        <div class="small text-body-secondary d-flex flex-wrap gap-3 mb-3">
            <span>Points: {{ $chore->base_points }}</span>
            <span>Due: {{ optional($chore->due_at)?->format('M j, Y g:i A') ?? 'Any time' }}</span>
            <span>Assigned: {{ $chore->assignee?->name ?? 'Open' }}</span>
        </div>
        {{ $slot }}
    </div>
</div>
