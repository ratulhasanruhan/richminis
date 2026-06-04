<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class SetupPasswordController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showForm(Request $request, string $token)
    {
        $email = $request->query('email');

        if (empty($email)) {
            flash(translate('Invalid password setup link.'))->error();
            return redirect()->route('user.login');
        }

        $user = User::where('email', $email)->first();
        if (!$user || !Password::broker()->tokenExists($user, $token)) {
            flash(translate('This password setup link is invalid or has expired.'))->error();
            return redirect()->route('password.request');
        }

        return view('auth.setup_password', compact('email', 'token'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                auth()->login($user, true);
            }

            flash(translate('Password set successfully. You are now logged in.'))->success();
            return redirect()->route('dashboard');
        }

        flash(translate('This password setup link is invalid or has expired.'))->error();
        return back()->withInput($request->only('email'));
    }
}
