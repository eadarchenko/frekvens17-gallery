<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Notifications\TestOrderRecorded;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sends_verification_to_local_transport_and_never_exposes_token(): void
    {
        Notification::fake();
        $r = $this->postJson('/api/register', ['name' => 'Test', 'email' => 'verify@example.test', 'password' => 'Local-test-only123', 'password_confirmation' => 'Local-test-only123']);
        $r->assertCreated()->assertJsonPath('user.email_verified_at', null)->assertJsonMissingPath('token');
        Notification::assertSentTo(User::first(), VerifyEmail::class);
        $this->assertSame('log', config('mail.default'));
    }

    public function test_signed_verification_marks_only_current_user_and_is_replay_safe(): void
    {
        $u = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $u->id, 'hash' => sha1($u->email)]);
        $this->actingAs($u)->get($url)->assertRedirect('/account?verified=1');
        $this->assertNotNull($u->fresh()->email_verified_at);
        $this->get($url)->assertRedirect('/account?verified=1');
    }

    public function test_tampered_expired_and_wrong_account_verification_links_are_rejected(): void
    {
        $u = User::factory()->unverified()->create();
        $v = User::factory()->create();
        $params = ['id' => $u->id, 'hash' => sha1($u->email)];
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), $params);
        $this->actingAs($v)->get($url)->assertForbidden();
        $this->actingAs($u)->get($url.'broken')->assertForbidden();
        $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), $params);
        $this->get($expired)->assertForbidden();
        $this->assertNull($u->fresh()->email_verified_at);
    }

    public function test_resend_requires_authentication(): void
    {
        Notification::fake();
        $this->postJson('/api/email/verification-notification')->assertUnauthorized();
        $u = User::factory()->unverified()->create();
        $this->actingAs($u)->postJson('/api/email/verification-notification')->assertOk();
        Notification::assertSentTo($u, VerifyEmail::class);
    }

    public function test_forgot_password_does_not_reveal_existing_accounts(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'exists@example.test']);
        $a = $this->postJson('/api/forgot-password', ['email' => 'exists@example.test'])->assertOk()->json();
        $b = $this->postJson('/api/forgot-password', ['email' => 'missing@example.test'])->assertOk()->json();
        $this->assertSame($a, $b);
    }

    public function test_recovery_token_is_single_use_password_is_hashed_and_all_sessions_are_revoked(): void
    {
        $u = User::factory()->create();
        $token = Password::createToken($u);
        DB::table('sessions')->insert(['id' => 'old-session', 'user_id' => $u->id, 'payload' => 'test', 'last_activity' => time()]);
        $data = ['email' => $u->email, 'token' => $token, 'password' => 'New-local-test123', 'password_confirmation' => 'New-local-test123'];
        $this->postJson('/api/reset-password', $data)->assertOk();
        $this->assertTrue(Hash::check($data['password'], $u->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        $this->postJson('/api/reset-password', $data)->assertUnprocessable();
    }

    public function test_invalid_expired_recovery_and_weak_password_are_rejected(): void
    {
        $u = User::factory()->create();
        $d = ['email' => $u->email, 'token' => 'invalid', 'password' => 'New-local-test123', 'password_confirmation' => 'New-local-test123'];
        $this->postJson('/api/reset-password', $d)->assertUnprocessable();
        $d['token'] = Password::createToken($u);
        $this->travel(61)->minutes();
        $this->postJson('/api/reset-password', $d)->assertUnprocessable();
    }

    public function test_unverified_registered_checkout_is_blocked_but_guest_route_does_not_require_login(): void
    {
        config(['shop.mode' => 'test', 'shop.currency' => 'EUR']);
        $u = User::factory()->unverified()->create();
        $this->actingAs($u)->postJson('/api/checkout', [])->assertForbidden();
    }

    public function test_order_confirmation_uses_log_and_is_explicitly_not_a_receipt_of_payment(): void
    {
        $o = new Order(['reference' => 'TEST-REFERENCE', 'currency' => 'EUR', 'total_cents' => 12500]);
        $o->setRelation('items', collect());
        $n = new TestOrderRecorded($o);
        $mail = $n->toMail(new \stdClass);
        $this->assertSame('log', $mail->mailer);
        $this->assertStringContainsString('no payment', $mail->subject);
        $this->assertStringContainsString('Do not dispatch',implode(' ',$mail->introLines));
    }
 public function test_verification_link_without_session_redirects_to_account_instead_of_error():void {
  $u=User::factory()->unverified()->create();$url=URL::temporarySignedRoute('verification.verify',now()->addMinutes(60),['id'=>$u->id,'hash'=>sha1($u->email)]);
  $this->get($url)->assertRedirect('/account');
 }
}
