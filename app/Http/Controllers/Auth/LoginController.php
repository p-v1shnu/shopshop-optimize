<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    use AuthenticatesUsers {
        logout as performLogout;
    }

    public function showLoginForm()
    {
        $intentUrl = redirect()->intended()->getTargetUrl();
        $intentUri = str_replace(url('/'), '', $intentUrl);

        return redirect()->route('home', [
            'action'   => 'login',
            'redirect' => $intentUri,
        ]);
    }

//    protected function sendLoginResponse(Request $request): RedirectResponse
//    {
//        $request->session()->regenerate();
//
//        $this->clearLoginAttempts($request);
//
//        return redirect()->route('home');
//    }

    public function logout(Request $request): RedirectResponse
    {
        $this->performLogout($request);

        return redirect()->route('home');
    }
}
