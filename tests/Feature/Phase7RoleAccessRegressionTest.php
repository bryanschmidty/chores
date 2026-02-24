<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
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

it('prevents kid and parent users from using supervisor approval queue/actions', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $parent->syncRoles(['parent']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
    ]);

    $completion = ChoreCompletion::factory()->create([
        'household_id' => $household->id,
        'chore_instance_id' => $chore->id,
        'completed_by_user_id' => $kid->id,
        'approval_status' => ApprovalStatus::Pending->value,
    ]);

    ChoreCompletionParticipant::factory()->create([
        'chore_completion_id' => $completion->id,
        'user_id' => $kid->id,
        'is_primary' => true,
    ]);

    $this->actingAs($kid)->get(route('supervisor.queue'))->assertForbidden();
    $this->actingAs($parent)->get(route('supervisor.queue'))->assertForbidden();

    $this->actingAs($kid)
        ->post(route('chore-completions.approve', $completion), [])
        ->assertForbidden();

    $this->actingAs($parent)
        ->post(route('chore-completions.approve', $completion), [])
        ->assertForbidden();
});
