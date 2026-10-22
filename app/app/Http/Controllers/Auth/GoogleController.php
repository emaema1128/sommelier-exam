<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Redirect the user to Google's consent screen.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Log the user in (registering or linking the account if needed) after Google authentication.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->failed('Googleでのログインに失敗しました。もう一度お試しください。');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            // Linking or creating by email is only safe when Google has verified the address.
            if (! ($googleUser->getRaw()['email_verified'] ?? false)) {
                return $this->failed('Googleアカウントのメールアドレスが確認されていません。');
            }

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                $user->google_id = $googleUser->getId();
                $user->email_verified_at ??= now();
                $user->save();
            } else {
                $user = new User;
                $user->name = $googleUser->getName() ?: $googleUser->getEmail();
                $user->email = $googleUser->getEmail();
                $user->google_id = $googleUser->getId();
                $user->email_verified_at = now();
                $user->save();

                event(new Registered($user));
            }
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function failed(string $message): RedirectResponse
    {
        return redirect()->route('login')->withErrors(['email' => $message]);
    }
}
