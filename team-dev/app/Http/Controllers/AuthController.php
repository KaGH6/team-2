<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller {
    // サインアップ画面表示
    public function showRegisterForm() {
        return view('auth.register');
    }

    // サインアップ処理
    public function register(Request $request) {
        $request->validate([
            'name'                  => 'required|string|max:50',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        // ユーザー作成
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 自動ログイン
        Auth::login($user);

        return redirect()->route('home');
    }

    // ログイン画面表示
    public function showLoginForm() {
        return view('auth.login');
    }

    // ログイン処理
    public function login(Request $request) {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->filled('remember'))) {
            // セッション再生成（セキュリティ対策）
            $request->session()->regenerate();
            return redirect()->intended(route('home'));
        }

        return back()->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    // ログアウト処理
    public function logout(Request $request) {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
