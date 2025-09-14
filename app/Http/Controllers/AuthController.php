<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * Show the login form
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Handle user login
     */
    public function login(Request $request)
    {
        // Validate the login request
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password'));
        }

        // Attempt to authenticate the user
        $credentials = $request->only('email', 'password');
        $result = $this->authService->login($credentials, $request->filled('remember'));

        if ($result['success']) {
            $request->session()->regenerate();
            return redirect()->intended($result['redirect_url']);
        }

        // If authentication fails
        return redirect()->back()
            ->withErrors(['email' => $result['error']])
            ->withInput($request->except('password'));
    }

    /**
     * Show the registration form
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Handle user registration
     */
    public function register(Request $request)
    {
        // Validate the registration request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $result = $this->authService->register($request->all());

        if ($result['success']) {
            return redirect()->route('patient.dashboard')
                ->with('success', $result['message']);
        } else {
            return redirect()->back()
                ->with('error', $result['error'])
                ->withInput($request->except('password', 'password_confirmation'));
        }
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        $result = $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', $result['message']);
    }

    /**
     * Show user profile
     */
    public function profile()
    {
        $user = Auth::user();
        return view('auth.profile', compact('user'));
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }

        $result = $this->authService->updateProfile($user, $request->all());

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        } else {
            return redirect()->back()->with('error', $result['error']);
        }
    }
}
