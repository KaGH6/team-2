@extends('layouts.app')

@section('content')
<div class="auth-bg">
    <div class="auth-card">
        <h2 class="auth-title">ログイン</h2>
        <form method="POST" action="{{ route('login') }}" class="auth-form">
            @csrf
            <div class="auth-group">
                <label for="auth-email">メールアドレス</label>
                <input type="email" id="auth-email" name="email" class="auth-input" required value="{{ old('email') }}">
                @error('email')
                <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="auth-group">
                <label for="auth-password">パスワード</label>
                <input type="password" id="auth-password" name="password" class="auth-input" required>
                @error('password')
                <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>
            <div class="auth-group" style="margin-bottom:0;">
                <label class="auth-remember">
                    <input type="checkbox" name="remember">
                    次回から自動ログイン
                </label>
            </div>
            <button type="submit" class="auth-btn">ログイン</button>
        </form>
        <div class="auth-bottom">
            <a href="{{ route('register') }}" class="auth-link">アカウントをお持ちでない方はこちら</a>
        </div>
    </div>
</div>
@endsection