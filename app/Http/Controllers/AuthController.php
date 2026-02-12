<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }
        return view('auth.login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nomber_tel' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('nomber_tel', $request->nomber_tel)->first();

        if (!$user) {
            return back()->with('message', 'Пользователь не найден.');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->with('message', 'Неверный пароль.');
        }

        Auth::login($user);
        $request->session()->regenerate();
        session(['FIO' => $user->full_name]);

        return $this->redirectByRole($user)->with('login_success', 'Вы успешно вошли в систему!');
    }

    public function register(Request $request)
    {
        $request->validate([
            'lastname' => ['required', 'regex:/^[А-Яа-яЁё\s\-]+$/u'],
            'name' => ['required', 'regex:/^[А-Яа-яЁё\s\-]+$/u'],
            'firstname' => ['nullable', 'regex:/^[А-Яа-яЁё\s\-]+$/u'],
            'nomber_tel' => ['required', 'regex:/^\+7 \(\d{3}\) \d{3}-\d{2}-\d{2}$/'],
            'password' => 'required|min:6',
        ], [
            'lastname.regex' => 'Фамилия должна содержать только буквы.',
            'name.regex' => 'Имя должно содержать только буквы.',
            'firstname.regex' => 'Отчество должно содержать только буквы.',
            'nomber_tel.regex' => 'Введите номер телефона в формате +7 (XXX) XXX-XX-XX.',
            'password.min' => 'Пароль должен содержать минимум 6 символов.',
        ]);

        // Check for existing phone
        if (User::where('nomber_tel', $request->nomber_tel)->exists()) {
            return back()->with('message', 'Пользователь с таким номером телефона уже зарегистрирован.');
        }

        $user = User::create([
            'lastname' => trim($request->lastname),
            'name' => trim($request->name),
            'firstname' => trim($request->firstname) ?: null,
            'nomber_tel' => $request->nomber_tel,
            'password' => $request->password,
            'id_roli' => 0,
        ]);

        Auth::login($user);
        session(['FIO' => $user->full_name]);

        return redirect()->route('home')->with('registration_success', 'Регистрация прошла успешно! Добро пожаловать!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    protected function redirectByRole(User $user)
    {
        return match ($user->id_roli) {
            2 => redirect()->route('admin.index'),
            1 => redirect()->route('master.index'),
            default => redirect()->route('home'),
        };
    }
}
