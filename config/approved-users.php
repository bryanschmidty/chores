<?php

use Illuminate\Support\Str;

$approvedUsers = json_decode((string) env('APPROVED_USERS', '[{"name":"Parent User","email":"parent@example.com","role":["parent","supervisor"]},{"name":"Kid User","email":"kid@example.com","role":["kid"]},{"name":"Supervisor Kid","email":"supervisor.kid@example.com","role":["kid","supervisor"]}]'), true);

if (! is_array($approvedUsers)) {
    $approvedUsers = [];
}

$normalizedUsers = [];

foreach ($approvedUsers as $approvedUser) {
    if (! is_array($approvedUser)) {
        continue;
    }

    $email = isset($approvedUser['email']) ? strtolower(trim((string) $approvedUser['email'])) : '';
    $name = isset($approvedUser['name']) ? trim((string) $approvedUser['name']) : '';

    if ($email === '') {
        continue;
    }

    $rawRoles = $approvedUser['role'] ?? [];

    if (is_string($rawRoles)) {
        $rawRoles = [trim($rawRoles)];
    }

    if (! is_array($rawRoles)) {
        continue;
    }

    $roles = array_values(array_filter(array_unique(array_map(
        static fn (string $role): string => strtolower(trim($role)),
        $rawRoles
    )), static fn (string $role): bool => in_array($role, ['parent', 'kid', 'supervisor'], true)));

    if ($roles === []) {
        continue;
    }

    if (! array_key_exists($email, $normalizedUsers)) {
        $normalizedUsers[$email] = [
            'name' => $name !== '' ? $name : Str::before($email, '@'),
            'email' => $email,
            'roles' => $roles,
        ];

        continue;
    }

    $normalizedUsers[$email]['roles'] = array_values(array_unique(array_merge(
        $normalizedUsers[$email]['roles'],
        $roles
    )));
}

return [
    'users' => array_values($normalizedUsers),
];
