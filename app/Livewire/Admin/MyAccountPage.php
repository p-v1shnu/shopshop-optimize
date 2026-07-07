<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class MyAccountPage extends Component
{
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    public ?string $successMessage = null;

    public function changePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ]);

        $admin = Auth::guard('admin')->user();

        if (! Hash::check($validated['currentPassword'], $admin->password)) {
            $this->addError('currentPassword', 'The current password is incorrect.');
            return;
        }

        $admin->forceFill([
            'password' => Hash::make($validated['newPassword']),
        ])->save();

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->successMessage = 'Your password has been changed.';
    }

    public function render()
    {
        return view('admin.my-account-page')
            ->layout('admin.layout')
            ->title('My Account');
    }
}
