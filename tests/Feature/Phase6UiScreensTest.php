<?php

use App\Enums\ApprovalStatus;
use App\Enums\ChoreInstanceStatus;
use App\Enums\ChoreSourceType;
use App\Enums\RecurrenceType;
use App\Models\ChoreCompletion;
use App\Models\ChoreCompletionParticipant;
use App\Models\ChoreInstance;
use App\Models\ChoreTemplate;
use App\Models\Household;
use App\Models\User;
use App\Models\WeeklyClaim;
use Carbon\CarbonImmutable;
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
    $this->actingAs($parent)->get(route('app.home'))->assertRedirect(route('parent.chores'));
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
        ->assertSee('# unclaimed chores')
        ->assertSee('# My Points')
        ->assertSee('My Chores')
        ->assertSee('My Weekly Chores')
        ->assertSee('My Completed Chores')
        ->assertSee('Unclaimed');

    $this->actingAs($kid)->get(route('kid.chores'))
        ->assertRedirect(route('kid.index', ['filter' => 'unclaimed']));

    $this->actingAs($kid)->get(route('kid.chores.show', $chore))
        ->assertSuccessful()
        ->assertSee('Claim Chore')
        ->assertSee('Submit Completion');
});

it('hides claim card in chore details when chore is already assigned', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $otherKid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);
    $otherKid->syncRoles(['kid']);

    $assignedChore = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $otherKid->id,
        'status' => ChoreInstanceStatus::Due->value,
    ]);

    $this->actingAs($kid)->get(route('kid.chores.show', $assignedChore))
        ->assertSuccessful()
        ->assertDontSee('Claim Chore')
        ->assertSee('Submit Completion');
});

it('shows only today chores in my chores filter and week chores in weekly filter', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Today chore',
        'due_at' => CarbonImmutable::now()->startOfDay(),
    ]);
    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Tomorrow chore',
        'due_at' => CarbonImmutable::now()->addDay()->startOfDay(),
    ]);

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'my_chores']))
        ->assertSuccessful()
        ->assertSee('Today chore')
        ->assertDontSee('Tomorrow chore');

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'my_weekly_chores']))
        ->assertSuccessful()
        ->assertSee('Today chore')
        ->assertSee('Tomorrow chore');

    CarbonImmutable::setTestNow();
});

it('excludes submitted and approved chores from my chores and shows them in my completed chores', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Active today',
        'status' => ChoreInstanceStatus::Due->value,
        'due_at' => CarbonImmutable::now()->startOfDay(),
    ]);
    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Submitted chore',
        'status' => ChoreInstanceStatus::PendingApproval->value,
        'due_at' => CarbonImmutable::now()->startOfDay(),
    ]);
    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Approved chore',
        'status' => ChoreInstanceStatus::Approved->value,
        'due_at' => CarbonImmutable::now()->startOfDay(),
    ]);

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'my_chores']))
        ->assertSuccessful()
        ->assertSee('Active today')
        ->assertDontSee('Submitted chore')
        ->assertDontSee('Approved chore');

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'my_completed_chores']))
        ->assertSuccessful()
        ->assertSee('Submitted chore')
        ->assertSee('Approved chore')
        ->assertSee('Pending Approval')
        ->assertSee('Approved')
        ->assertDontSee('Active today');

    CarbonImmutable::setTestNow();
});

it('shows unclaimed chores without due date in unclaimed filter', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => null,
        'title' => 'No due date chore',
        'due_at' => null,
    ]);

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'unclaimed']))
        ->assertSuccessful()
        ->assertSee('No due date chore');

    CarbonImmutable::setTestNow();
});

it('shows assigned chores without due date in my chores filter', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'assigned_to_user_id' => $kid->id,
        'title' => 'Assigned no due date',
        'due_at' => null,
    ]);

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'my_chores']))
        ->assertSuccessful()
        ->assertSee('Assigned no due date');

    CarbonImmutable::setTestNow();
});

it('shows empty-state card for my chores and unclaimed filter without recurring template section', function () {
    $household = Household::factory()->create();
    $kid = User::factory()->create(['household_id' => $household->id]);
    $kid->syncRoles(['kid']);

    ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $kid->id,
        'recurrence_type' => RecurrenceType::Weekly->value,
        'is_active' => true,
    ]);

    $this->actingAs($kid)
        ->get(route('kid.index'))
        ->assertSuccessful()
        ->assertSeeText('No claimed chores')
        ->assertSeeText('Tap above to switch to unclaimed chores and claim one.');

    $this->actingAs($kid)
        ->get(route('kid.index', ['filter' => 'unclaimed']))
        ->assertSuccessful()
        ->assertDontSee('Recurring This Week')
        ->assertDontSee('Claim for Week');
});

it('renders parent manage chores pages and redirects template create to manage chores', function () {
    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $parent->syncRoles(['parent']);

    $this->actingAs($parent)->post(route('chore-templates.store'), [
        'title' => 'Clean table',
        'description' => 'After dinner',
        'points' => 5,
        'recurrence_type' => 'daily',
    ])->assertRedirect(route('parent.chores'));

    $this->actingAs($parent)->get(route('parent.chores'))
        ->assertSuccessful()
        ->assertSee('Manage Chores')
        ->assertSee('Edit');

    $template = ChoreTemplate::query()->where('household_id', $household->id)->firstOrFail();
    $instance = ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'source_type' => ChoreSourceType::AdHoc->value,
    ]);

    $this->actingAs($parent)->get(route('parent.chores.recurring.edit', $template))
        ->assertSuccessful()
        ->assertSee('Edit Recurring Chore')
        ->assertSee('Claimed This Week')
        ->assertSee('Unclaimed');
    $this->actingAs($parent)->get(route('parent.chores.one-time.edit', $instance))
        ->assertSuccessful()
        ->assertSee('Edit One-time Chore');

    $this->actingAs($parent)->get(route('parent.chores.create'))
        ->assertSuccessful()
        ->assertSee('Add Chore')
        ->assertSee('Recurring chore')
        ->assertSee('One-time chore');
    $this->actingAs($parent)->get(route('parent.history'))->assertSuccessful();
    $this->actingAs($parent)->get(route('parent.leaderboard'))->assertSuccessful();
});

it('shows template claimant assignee and excludes recurring instances from one-time rows', function () {
    CarbonImmutable::setTestNow('2026-06-10 09:00:00');

    $household = Household::factory()->create();
    $parent = User::factory()->create(['household_id' => $household->id]);
    $kid = User::factory()->create(['household_id' => $household->id, 'name' => 'Claiming Kid']);
    $parent->syncRoles(['parent']);
    $kid->syncRoles(['kid']);

    $template = ChoreTemplate::factory()->create([
        'household_id' => $household->id,
        'created_by_user_id' => $parent->id,
        'title' => 'Recurring Dishwasher',
    ]);

    WeeklyClaim::factory()->create([
        'household_id' => $household->id,
        'chore_template_id' => $template->id,
        'assigned_to_user_id' => $kid->id,
        'claimed_by_user_id' => $kid->id,
        'week_start_at' => CarbonImmutable::now()->startOfWeek()->toDateString(),
        'week_end_at' => CarbonImmutable::now()->endOfWeek()->toDateString(),
    ]);

    ChoreInstance::factory()->create([
        'household_id' => $household->id,
        'chore_template_id' => $template->id,
        'source_type' => ChoreSourceType::Recurring->value,
        'title' => 'Recurring instance row',
    ]);

    $this->actingAs($parent)
        ->get(route('parent.chores'))
        ->assertSuccessful()
        ->assertSee('Recurring Dishwasher')
        ->assertSee('Claiming Kid')
        ->assertDontSee('Recurring instance row');

    CarbonImmutable::setTestNow();
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
