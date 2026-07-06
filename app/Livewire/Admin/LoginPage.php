<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LoginPage extends Component
{
    public string $email = '';

    public string $password = '';

    public function login()
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['status'] = 'active';

        if (!Auth::guard('admin')->attempt($credentials)) {
            $this->addError('email', 'These credentials do not match our records.');

            return null;
        }

        request()->session()->regenerate();
        Auth::guard('admin')->user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function render()
    {
        return view('admin.login-page')
            ->layout('admin.layout', ['guestLayout' => true])
            ->title('Admin Login');
    }
}
