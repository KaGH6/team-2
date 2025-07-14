@extends('layouts.app')

@section('content')
<h2>サインアップ</h2>
<form method="POST" action="{{ route('register') }}">
    @csrf
    <div>
        <label>名前</label>
        <input type="text" name="name" value="{{ old('name') }}" required>
        @error('name')<div>{{ $message }}</div>@enderror
    </div>
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
        <label>パスワード（確認用）</label>
        <input type="password" name="password_confirmation" required>
    </div>
    <button type="submit">登録する</button>
</form>
<a href="{{ route('login') }}">すでにアカウントがある方はこちら</a>
@endsection