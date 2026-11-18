<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function forgot(Request $r)
    {
        $d = $r->validate(['email' => 'required|email:rfc|max:254']);
        Password::sendResetLink(['email' => strtolower($d['email'])]);

        // Do not reveal whether an account exists or a reset is throttled.
        return ['message' => 'If this account exists, a recovery link has been written to the local mail log. No email was sent externally.'];
    }

    public function reset(Request $r)
    {
        $d = $r->validate(['email' => 'required|email:rfc|max:254', 'token' => 'required|string|max:256', 'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->numbers()]]);
        $d['email'] = strtolower($d['email']);
        $status = Password::reset($d, function (User $u, string $password) {
            $u->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            // Revoke all existing sessions after recovery, including a lost device.
            DB::table('sessions')->where('user_id', $u->id)->delete();
            event(new PasswordReset($u));
        });
        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => 'This recovery link is invalid or expired. Request a new one.']);
        }

        return ['message' => 'Password changed. Sign in with your new password.'];
    }

    public function resend(Request $r)
    {
        if (! $r->user()->hasVerifiedEmail()) {
            $r->user()->sendEmailVerificationNotification();
        }

        return ['message' => 'A verification link has been written to the local mail log. No email was sent externally.'];
    }

    public function verify(Request $r, string $id, string $hash)
    {
        abort_unless((string) $r->user()->getKey() === $id, 403);
        abort_unless(hash_equals(sha1($r->user()->getEmailForVerification()), $hash), 403);
        if (! $r->user()->hasVerifiedEmail() && $r->user()->markEmailAsVerified()) {
            event(new Verified($r->user()));
        }

        return redirect('/account?verified=1');
    }
}
