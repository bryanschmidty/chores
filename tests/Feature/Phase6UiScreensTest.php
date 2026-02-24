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

it('routes app home by role', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $parent = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);

    $kid->syncRoles(['kid']);
    $parent->syncRoles(['parent']);
    $supervisor->syncRoles(['supervisor']);

    $this->actingAs($kid)->get(route('app.home'))->assertRedirect(route('kid.index'));
    $this->actingAs($parent)->get(route('app.home'))->assertRedirect(route('parent.templates'));
    $this->actingAs($supervisor)->get(route('app.home'))->assertRedirect(route('supervisor.queue'));
});

it('renders kid screens for household chores', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $helper = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $helper->syncRoles(['kid']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'status' => ChoreInstanceStatus::Due->value,
        'title' => 'Take out trash',
    ]);

    $this->actingAs($kid)->get(route('kid.index'))
        ->assertSuccessful()
        ->assertSee('My Chores');

    $this->actingAs($kid)->get(route('kid.chores'))
        ->assertSuccessful()
        ->assertSee('All Chores');

    $this->actingAs($kid)->get(route('kid.chores.show', $chore))
        ->assertSuccessful()
        ->assertSee('Submit Completion');
});

it('renders parent pages and redirects template create back to parent template screen', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent']);

    $this->actingAs($parent)->get(route('parent.templates'))
        ->assertSuccessful()
        ->assertSee('Recurring Templates');

    $this->actingAs($parent)->post(route('chore-templates.store'), [
        'title' => 'Clean table',
        'description' => 'After dinner',
        'points' => 5,
        'recurrence_type' => 'daily',
    ])->assertRedirect(route('parent.templates'));

    $this->actingAs($parent)->get(route('parent.chores'))
        ->assertSuccessful()
        ->assertSee('Manage Chores');
    $this->actingAs($parent)->get(route('parent.history'))->assertSuccessful();
    $this->actingAs($parent)->get(route('parent.leaderboard'))->assertSuccessful();
});

it('renders supervisor queue and redirects approval actions to queue', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $supervisor = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $supervisor->syncRoles(['supervisor']);

    $chore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'status' => ChoreInstanceStatus::PendingApproval->value,
        'title' => 'Vacuum living room',
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

    $this->actingAs($supervisor)->get(route('supervisor.queue'))
        ->assertSuccessful()
        ->assertSee('Vacuum living room');

    $this->actingAs($supervisor)->post(route('chore-completions.approve', $completion), [])
        ->assertRedirect(route('supervisor.queue'));
});
