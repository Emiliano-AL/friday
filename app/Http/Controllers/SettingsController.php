<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Show the account settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Settings/Index', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'hasPassword' => $user->password !== null,
                'usesGoogle' => $user->oauth_provider !== null,
            ],
            'ownedProjects' => Project::query()
                ->where('owner_id', $user->id)
                ->orderBy('title')
                ->get(['id', 'title', 'color']),
        ]);
    }

    /**
     * Update the user's name; the email is read-only here.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $request->user()->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        return back()->with('success', 'Perfil actualizado.');
    }

    /**
     * Change the password (or define one for Google accounts) and sign out other devices.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $hadPassword = $user->password !== null;

        $validated = $request->validate([
            'current_password' => $hadPassword ? ['required', 'current_password'] : ['exclude'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);
        Auth::logoutOtherDevices($validated['password']);

        return back()->with('success', $hadPassword ? 'Contraseña actualizada.' : 'Contraseña definida.');
    }

    /**
     * Delete the account once confirmed, unless the user still owns projects.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $field = $user->password !== null ? 'password' : 'confirmation';

        $request->validate([
            $field => $field === 'password'
                ? ['required', 'current_password']
                : ['required', 'string', fn (string $attribute, mixed $value, Closure $fail) => Str::lower(trim((string) $value)) === Str::lower($user->email)
                    ? null
                    : $fail('La confirmación no coincide.')],
        ], [
            'password.current_password' => 'La confirmación no coincide.',
        ]);

        if (Project::query()->where('owner_id', $user->id)->exists()) {
            throw ValidationException::withMessages([$field => 'Elimina tus proyectos antes de eliminar tu cuenta.']);
        }

        // Log out first: logging out afterwards would save the remember token and re-insert the user.
        Auth::logout();

        DB::transaction(function () use ($user): void {
            Task::query()
                ->whereNull('project_id')
                ->where(fn ($query) => $query->where('author_id', $user->id)->orWhere('assignee_id', $user->id))
                ->delete();

            $user->delete();
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
