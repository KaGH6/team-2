@extends('layouts.app')

@section('content')
<div class="auth-bg">
    <div class="auth-card">
        <h2 class="auth-title">サインアップ</h2>
        <form method="POST" action="{{ route('register') }}" class="auth-form">
            @csrf
            <div class="auth-group">
                <label for="auth-name">名前</label>
                <input type="text" id="auth-name" name="name" class="auth-input" required value="{{ old('name') }}">
                @error('name')
                <div class="auth-error">{{ $message }}</div>
                @enderror
            </div>
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
            <div class="auth-group">
                <label for="auth-password-confirm">パスワード（確認用）</label>
                <input type="password" id="auth-password-confirm" name="password_confirmation" class="auth-input" required>
            </div>
            <button type="submit" class="auth-btn">登録する</button>
        </form>
        <div class="auth-bottom">
            <a href="{{ route('login') }}" class="auth-link">すでにアカウントがある方はこちら</a>
        </div>
    </div>
</div>

@endsection