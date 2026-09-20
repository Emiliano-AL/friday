<?php

namespace App\Http\Controllers\Auth;

use App\Enums\OAuthProvider;
use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to Google's OAuth consent screen.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the OAuth callback: create, link, or authenticate the user.
     */
    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException|ClientException) {
            return $this->failedLogin();
        }

        $user = User::query()
            ->where('oauth_provider', OAuthProvider::Google->value)
            ->where('oauth_id', $googleUser->getId())
            ->first()
            ?? $this->linkByEmail($googleUser);

        $user ??= User::query()->create([
            'name' => $googleUser->getName() ?? str($googleUser->getEmail())->before('@')->toString(),
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getAvatar(),
            'oauth_provider' => OAuthProvider::Google->value,
            'oauth_id' => $googleUser->getId(),
        ]);

        $user->email_verified_at ??= Carbon::now();
        $user->save();

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->intended(route('home', absolute: false));
    }

    /**
     * Link a Google identity to an existing local account by verified email.
     */
    private function linkByEmail(SocialiteUser $googleUser): ?User
    {
        $user = User::query()->where('email', $googleUser->getEmail())->first();

        if ($user === null) {
            return null;
        }

        $user->forceFill([
            'oauth_provider' => OAuthProvider::Google->value,
            'oauth_id' => $googleUser->getId(),
            'avatar' => $user->avatar ?? $googleUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? Carbon::now(),
        ])->save();

        return $user;
    }

    /**
     * Redirect back to login with a generic error after a failed OAuth attempt.
     */
    private function failedLogin(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('status', 'No pudimos iniciar sesión con Google. Inténtalo de nuevo.');
    }
}
