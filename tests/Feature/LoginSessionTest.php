<?php

namespace Tests\Feature;

use App\Events\LoginApproved;
use App\Models\LoginSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LoginSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_qr_session()
    {
        $response = $this->postJson('/api/qr/session');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'uuid',
            'url',
        ]);

        $this->assertDatabaseHas('login_sessions', [
            'status' => 'waiting',
        ]);
    }

    public function test_user_can_approve_qr_session()
    {
        Event::fake();

        $user = User::factory()->create();

        $session = LoginSession::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson("/qr/approve/{$session->uuid}");

        $response->assertStatus(200);

        $response->assertJson([
            'ok' => true,
        ]);

        $this->assertDatabaseHas('login_sessions', [
            'uuid' => $session->uuid,
            'status' => 'approved',
            'user_id' => $user->id,
        ]);

        Event::assertDispatched(LoginApproved::class);
    }

    public function test_expired_qr_session_cannot_be_approved()
    {
        $user = User::factory()->create();

        $session = LoginSession::create([
            'uuid' => fake()->uuid(),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'expires_at' => now()->subMinute(),
            'status' => 'waiting',
        ]);

        $response = $this
            ->actingAs($user)
            ->postJson("/qr/approve/{$session->uuid}");

        $response->assertStatus(410);

        $response->assertJson([
            'ok' => false,
            'reason' => 'expired',
        ]);

        $this->assertDatabaseHas('login_sessions', [
            'uuid' => $session->uuid,
            'status' => 'waiting',
        ]);
    }

    public function test_already_processed_qr_session_cannot_be_approved()
    {
        $user = User::factory()->create();

        $session = LoginSession::factory()->approved()->create(
            [
                'user_id' => $user->id,
            ],
        );

        $response = $this
            ->actingAs($user)
            ->postJson("/qr/approve/{$session->uuid}");

        $response->assertStatus(409);

        $response->assertJson([
            'ok' => false,
            'reason' => 'already_processed',
        ]);
    }

    public function test_approved_qr_session_can_be_consumed()
    {
        $user = User::factory()->create();

        $session = LoginSession::factory()->approved()->create(
            [
                'user_id' => $user->id,
            ]
        );

        $response = $this->postJson("/qr/consume/{$session->uuid}");

        $response->assertStatus(200);

        $response->assertJson([
            'ok' => true,
        ]);

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseMissing('login_sessions', [
            'uuid' => $session->uuid,
        ]);
    }

    public function test_waiting_qr_session_cannot_be_consumed()
    {
        $session = LoginSession::factory()->create();

        $response = $this->postJson("/qr/consume/{$session->uuid}");

        $response->assertStatus(404);
    }

    public function test_expired_qr_session_cannot_be_consumed()
    {
        $session = LoginSession::factory()->expired()->create();

        $response = $this->postJson("/qr/consume/{$session->uuid}");

        $response->assertStatus(404);

        $this->assertGuest();
    }

    public function test_qr_session_can_be_deleted()
    {
        $session = LoginSession::factory()->create();

        $response = $this->deleteJson("api/qr/session/{$session->uuid}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('login_sessions', [
            'uuid' => $session->uuid,
        ]);
    }

    public function test_can_get_qr_session_info()
    {
        $session = LoginSession::factory()->create();

        $response = $this->getJson("api/qr/session/{$session->uuid}/info");

        $response->assertStatus(200);

        $response->assertJson(
            [
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
            ]
        );
    }

    public function test_user_can_open_approve_page()
    {
        $session = LoginSession::factory()->create();

        $response = $this->get("qr/{$session->uuid}");

        $response->assertStatus(302);
    }

    public function test_authenticated_user_gets_404_for_unknown_qr_uuid()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/qr/doesnt-exist-uuid');

        $response->assertNotFound();
    }

    public function test_guest_is_redirected_to_login_when_opening_qr_page()
    {
        $response = $this->get('/qr/doesnt-exist-uuid');

        $response->assertRedirect('/login');
    }

    public function test_guest_cannot_reject_qr_session()
    {
        $session = LoginSession::factory()->create();

        $response = $this->post("/qr/reject/{$session->uuid}/");

        $response->assertRedirect('/login');
    }

    public function test_user_can_reject_qr_session()
    {
        $user = User::factory()->create();

        $session = LoginSession::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson("/qr/reject/{$session->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseHas('login_sessions', [
            'uuid' => $session->uuid,
            'status' => 'rejected',
        ]);
    }
}
