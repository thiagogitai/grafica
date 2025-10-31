<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Para onde redirecionar após login.
     */
    protected $redirectTo = '/home';

    /**
     * Redireciona conforme perfil após autenticação.
     */
    protected function authenticated($request, $user)
    {
        if ($user && $user->is_admin) {
            return redirect()->intended(route('admin.dashboard'));
        }
        return redirect()->intended(route('home'));
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }
}
