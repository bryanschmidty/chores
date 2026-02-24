@props(['status'])

@php
    $normalizedStatus = is_string($status) ? $status : ($status?->value ?? 'unknown');
    $badgeClass = match ($normalizedStatus) {
        'due' => 'text-bg-primary',
        'overdue' => 'text-bg-danger',
        'pending_approval' => 'text-bg-warning',
        'approved' => 'text-bg-success',
        default => 'text-bg-secondary',
    };
@endphp

<span {{ $attributes->merge(['class' => 'badge ' . $badgeClass]) }}>
    {{ str_replace('_', ' ', $normalizedStatus) }}
</span>
