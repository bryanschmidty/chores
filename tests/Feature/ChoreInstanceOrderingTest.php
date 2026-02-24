<?php

use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('orders default kid list as my assigned then open then future recurring', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);

    $myAssignedFirst = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
        'status' => ChoreInstanceStatus::Due->value,
        'due_at' => CarbonImmutable::now()->addDay(),
    ]);
    $myAssignedSecond = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
        'status' => ChoreInstanceStatus::Upcoming->value,
        'due_at' => CarbonImmutable::now()->addDays(2),
    ]);
    $openDue = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'source_type' => ChoreSourceType::AdHoc->value,
        'status' => ChoreInstanceStatus::Due->value,
        'due_at' => CarbonImmutable::now()->addDay(),
    ]);
    $futureRecurringWithinWindow = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'source_type' => ChoreSourceType::Recurring->value,
        'status' => ChoreInstanceStatus::Upcoming->value,
        'due_at' => CarbonImmutable::now()->addDays(6),
    ]);
    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'source_type' => ChoreSourceType::Recurring->value,
        'status' => ChoreInstanceStatus::Upcoming->value,
        'due_at' => CarbonImmutable::now()->addDays(9),
    ]);
    ChoreInstance::factory()->create([
        'household_id' => Household::factory()->create()->id,
        'assigned_to_user_id' => null,
        'source_type' => ChoreSourceType::Recurring->value,
        'status' => ChoreInstanceStatus::Upcoming->value,
        'due_at' => CarbonImmutable::now()->addDays(3),
    ]);

    $response = $this->actingAs($kid)
        ->get(route('chore-instances.index'))
        ->assertSuccessful();

    $instanceIds = collect($response->json())->pluck('id')->values()->all();

    expect($instanceIds)->toBe([
        $myAssignedFirst->id,
        $myAssignedSecond->id,
        $openDue->id,
        $futureRecurringWithinWindow->id,
    ]);
});

it('does not duplicate chores that satisfy multiple segments', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);

    $assignedRecurring = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::Recurring->value,
        'status' => ChoreInstanceStatus::Upcoming->value,
        'due_at' => CarbonImmutable::now()->addDays(3),
    ]);

    $response = $this->actingAs($kid)
        ->get(route('chore-instances.index'))
        ->assertSuccessful();

    $instanceIds = collect($response->json())->pluck('id');

    expect($instanceIds->filter(fn (int $id): bool => $id === $assignedRecurring->id)->count())->toBe(1);
});
