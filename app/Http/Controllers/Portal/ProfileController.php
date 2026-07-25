<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Services\UserPhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function show(Request $request)
    {
        return view('portal.profile.show', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Show the profile edit form.
     */
    public function edit(Request $request)
    {
        return view('portal.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request, UserPhotoService $photoService)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,'.$user->id,
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        if ($request->hasFile('photo')) {
            $photoService->store($user, $request->file('photo'));
        }

        unset($validated['photo']);
        $user->update($validated);

        return redirect()->route('profile.show')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    /**
     * Remove the profile photo.
     */
    public function destroyPhoto(Request $request, UserPhotoService $photoService)
    {
        $photoService->destroy($request->user());

        return redirect()->route('profile.edit')
            ->with('status', 'Foto profil berhasil dihapus.');
    }

    /**
     * Show the change password form.
     */
    public function showChangePasswordForm()
    {
        return view('portal.profile.password');
    }

    /**
     * Change the user's password.
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')
            ->with('status', 'Password berhasil diubah.');
    }
}
