@extends('layouts.app')

@section('content')
<h2>ログイン</h2>
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div>
        <label>メールアドレス</label>
        <input type="email" name="email" value="{{ old('email') }}" required>
        @error('email')<div>{{ $message }}</div>@enderror
    </div>
    <div>
        <label>パスワード</label>
        <input type="password" name="password" required>
        @error('password')<div>{{ $message }}</div>@enderror
    </div>
    <div>
        <label>
            <input type="checkbox" name="remember"> 次回から自動ログイン
        </label>
    </div>
    <button type="submit">ログイン</button>
</form>
<a href="{{ route('register') }}">アカウントをお持ちでない方はこちら</a>
@endsection