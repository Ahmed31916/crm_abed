<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Account;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $account;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['type' => 'company']);
        $this->account = Account::factory()->create(['created_by' => $this->user->id]);
        
        $this->actingAs($this->user);
    }

    public function test_can_view_projects_index()
    {
        Project::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'account_id' => $this->account->id
        ]);

        $response = $this->get(route('projects.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('projects/index'));
    }

    public function test_can_create_project()
    {
        $projectData = [
            'name' => 'Test Project',
            'code' => 'TEST-001',
            'description' => 'Test project description',
            'account_id' => $this->account->id,
            'priority' => 'medium',
            'status' => 'active',
            'budget' => 10000.00
        ];

        $response = $this->post(route('projects.store'), $projectData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'code' => 'TEST-001',
            'account_id' => $this->account->id,
            'created_by' => $this->user->id
        ]);
    }

    public function test_can_view_project()
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'account_id' => $this->account->id
        ]);

        $response = $this->get(route('projects.show', $project));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('projects/show'));
    }

    public function test_can_update_project()
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'account_id' => $this->account->id
        ]);

        $updateData = [
            'name' => 'Updated Project Name',
            'code' => 'UPD-001',
            'account_id' => $this->account->id,
            'priority' => 'high',
            'status' => 'active'
        ];

        $response = $this->put(route('projects.update', $project), $updateData);

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name',
            'code' => 'UPD-001'
        ]);
    }

    public function test_can_delete_project()
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'account_id' => $this->account->id
        ]);

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertRedirect();
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_can_toggle_project_status()
    {
        $project = Project::factory()->create([
            'created_by' => $this->user->id,
            'account_id' => $this->account->id,
            'status' => 'active'
        ]);

        $response = $this->put(route('projects.toggle-status', $project));

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'inactive'
        ]);
    }

    public function test_cannot_access_other_users_projects()
    {
        $otherUser = User::factory()->create(['type' => 'company']);
        $otherAccount = Account::factory()->create(['created_by' => $otherUser->id]);
        $otherProject = Project::factory()->create([
            'created_by' => $otherUser->id,
            'account_id' => $otherAccount->id
        ]);

        $response = $this->get(route('projects.show', $otherProject));

        $response->assertStatus(404);
    }
}