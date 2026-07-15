<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class GoogleController extends Controller
{
    /**
     * Send the user off to Google's consent screen.
     */
    public function redirect(): SymfonyRedirect
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the callback: sign in an existing account, or register a new one
     * on the fly, then log the user straight in.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in failed or was cancelled. Please try again.');
        }

        if (! $googleUser->getEmail()) {
            return redirect()->route('login')
                ->with('error', 'Your Google account did not share an e-mail address.');
        }

        // Match on the Google id first, then fall back to the e-mail so an
        // existing password account gets linked instead of duplicated.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?: User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            $user->forceFill([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();
        } else {
            $user = User::create([
                'name' => $googleUser->getName()
                    ?: $googleUser->getNickname()
                    ?: Str::before($googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'role' => 'employee',
            ]);

            // Google already verified the address for us.
            $user->forceFill(['email_verified_at' => now()])->save();

            event(new Registered($user));
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
