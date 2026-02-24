<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreInstance;
use App\Models\Household;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

beforeEach(function () {
    seed(RoleSeeder::class);
});

it('allows only supervisors to assign chores', function () {
    $household = Household::factory()->create();
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'status' => ChoreInstanceStatus::Due->value,
        'source_type' => ChoreSourceType::AdHoc->value,
    ]);

    $supervisor->assignRole('supervisor');
    $kid->assignRole('kid');

    expect($supervisor->can('assign', $chore))->toBeTrue()
        ->and($kid->can('assign', $chore))->toBeFalse();
});

it('allows claim only for open due chores in the same household', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->assignRole('kid');

    $openDue = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $assignedDue = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => User::factory()->create(['household_id' => $household->id])->id,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $openUpcoming = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'status' => ChoreInstanceStatus::Upcoming->value,
    ]);

    expect($kid->can('claim', $openDue))->toBeTrue()
        ->and($kid->can('claim', $assignedDue))->toBeFalse()
        ->and($kid->can('claim', $openUpcoming))->toBeFalse();
});

it('allows supervisor approval but blocks self approval', function () {
    $household = Household::factory()->create();
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id]);
    $selfCompleterSupervisor = User::factory()->create(['household_id' => $household->id]);

    $supervisor->assignRole('supervisor');
    $kid->assignRole('kid');
    $selfCompleterSupervisor->assignRole('supervisor');

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    $completionByKid = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    $completionBySupervisor = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $selfCompleterSupervisor->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    expect($supervisor->can('approve', $completionByKid))->toBeTrue()
        ->and($selfCompleterSupervisor->can('approve', $completionBySupervisor))->toBeFalse()
        ->and($kid->can('approve', $completionByKid))->toBeFalse();
});
