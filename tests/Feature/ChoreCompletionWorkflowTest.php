<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
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

it('submits completion with helpers and marks chore pending approval', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $helper->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'source_type' => ChoreSourceType::AdHoc->value,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $this->actingAs($kid)
        ->post(route('chore-completions.store'), [
            'chore_instance_id' => $chore->id,
            'helper_user_ids' => [$helper->id],
        ])
        ->assertRedirect();

    $completion = ChoreCompletion::query()->where('chore_instance_id', $chore->id)->firstOrFail();

    expect($completion->approval_status)->toBe(ApprovalStatus::Pending)
        ->and($completion->completed_by_user_id)->toBe($kid->id);

    $participants = ChoreCompletionParticipant::query()
        ->where('chore_completion_id', $completion->id)
        ->get();

    expect($participants)->toHaveCount(2)
        ->and($participants->where('is_primary', true)->first()?->user_id)->toBe($kid->id)
        ->and($participants->where('is_primary', false)->first()?->user_id)->toBe($helper->id);

    expect($chore->fresh()->status)->toBe(ChoreInstanceStatus::PendingApproval);
});

it('blocks submission when pending completion already exists', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    $this->actingAs($kid)
        ->post(route('chore-completions.store'), [
            'chore_instance_id' => $chore->id,
        ])
        ->assertSessionHasErrors('chore_instance_id');
});

it('requires new submission after rejection and allows resubmission', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $this->actingAs($kid)->post(route('chore-completions.store'), [
        'chore_instance_id' => $chore->id,
    ])->assertRedirect();

    $firstCompletion = ChoreCompletion::query()->where('chore_instance_id', $chore->id)->firstOrFail();

    $this->actingAs($supervisor)->post(route('chore-completions.reject', $firstCompletion), [
        'rejection_reason' => 'Needs better quality.',
    ])->assertRedirect();

    $this->actingAs($kid)->post(route('chore-completions.store'), [
        'chore_instance_id' => $chore->id,
    ])->assertRedirect();

    expect(ChoreCompletion::query()->where('chore_instance_id', $chore->id)->count())->toBe(2)
        ->and($firstCompletion->fresh()->approval_status)->toBe(ApprovalStatus::Rejected);
});
