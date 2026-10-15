<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GoogleAuthController extends Controller
{
    protected function googleDriver(): GoogleProvider
    {
        /** @var GoogleProvider $driver */
        $driver = Socialite::driver('google');

        // Respeta el bundle de certificados configurado en PHP (útil en Windows sin cacert global).
        $verifyPath = collect([
            ini_get('curl.cainfo') ?: null,
            ini_get('openssl.cafile') ?: null,
        ])->first(fn (?string $path) => filled($path) && is_file($path));

        if ($verifyPath) {
            $driver->setHttpClient(new Client([
                'verify' => $verifyPath,
            ]));
        }

        return $driver;
    }

    public function redirect(Request $request): RedirectResponse
    {
        $selectedUsers = max(1, (int) $request->integer('users', (int) $request->session()->get('register.selected_users', 3)));
        $selectedBranches = max(0, (int) $request->integer('branches', (int) $request->session()->get('register.selected_branches', 1)));
        $selectedTypeId = max(0, (int) $request->integer('type_id', (int) $request->session()->get('register.selected_type_id', 0)));

        $request->session()->put('register.selected_users', $selectedUsers);
        $request->session()->put('register.selected_branches', $selectedBranches);
        $request->session()->put('register.selected_type_id', $selectedTypeId);

        return $this->googleDriver()
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = $this->googleDriver()->user();
        } catch (\Throwable $exception) {
            Log::warning('Google authentication failed.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'No fue posible iniciar sesion con Google. Intenta nuevamente.',
                ]);
        }

        $email = $googleUser->getEmail();

        if (blank($email)) {
            Log::warning('Google authentication returned an empty email.', [
                'google_id' => $googleUser->getId(),
            ]);

            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Google no devolvio un correo valido para esta cuenta.',
                ]);
        }

        $user = User::query()
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $email)
            ->first();

        if (! $user || ! filled($user->xnova_id)) {
            // Sin datos personales en el log: solo el id interno, si existe.
            Log::info('Google authentication redirected to onboarding.', [
                'user_id' => $user?->id,
            ]);

            $request->session()->put('register.google_onboarding', [
                'google_id' => $googleUser->getId(),
                'email' => $email,
                'name' => $googleUser->getName(),
            ]);

            return redirect()
                ->route('register', [
                    'users' => max(1, (int) $request->session()->get('register.selected_users', 3)),
                    'branches' => max(0, (int) $request->session()->get('register.selected_branches', 1)),
                    'type_id' => max(0, (int) $request->session()->get('register.selected_type_id', 0)),
                ])
                ->with('google_onboarding', [
                    'google_id' => $googleUser->getId(),
                    'email' => $email,
                    'name' => $googleUser->getName(),
                ])
                ->withInput([
                    'name' => $googleUser->getName(),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'registration_mode' => 'create_company',
                ]);
        }

        if (! $user->active) {
            return redirect()
                ->route('login')
                ->withErrors([
                    'google' => 'Este usuario está desactivado y no puede iniciar sesión.',
                ]);
        }

        $user->google_id = $googleUser->getId();
        $user->email_verified_at ??= now();
        $user->name = $googleUser->getName() ?: $user->name;
        $user->save();

        Log::info('Google authentication user authorized.', [
            'user_id' => $user->id,
        ]);

        Auth::guard('web')->login($user, remember: true);
        $request->session()->regenerate();
        $request->session()->forget([
            'google_onboarding',
            'register.google_onboarding',
            'register.selected_users',
            'register.selected_branches',
            'register.selected_type_id',
            'register.pending_setup',
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
