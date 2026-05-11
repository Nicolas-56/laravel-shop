@extends('layouts.app', ['title' => 'Вход'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="max-w-md mx-auto">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Account</span>
                        <h1>Вход</h1>
                    </div>
                </div>

                @if (session('status'))
                    <div class="flash flash--success">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="flash flash--error">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="form-card">
                    <form method="POST" action="{{ route('login') }}" class="form-grid">
                        @csrf

                        <label>
                            Email
                            <input
                                class="w-full border p-2 rounded"
                                name="email"
                                type="email"
                                value="{{ old('email') }}"
                                placeholder="Email"
                                required
                            >
                        </label>

                        <label>
                            Пароль
                            <input
                                class="w-full border p-2 rounded"
                                name="password"
                                type="password"
                                placeholder="Password"
                                required
                            >
                        </label>

                        <label class="checkbox-field">
                            <input type="checkbox" name="remember" value="1">
                            <span>Запомнить меня</span>
                        </label>

                        <button class="w-full bg-black text-white p-2 rounded" type="submit">
                            Войти
                        </button>
                    </form>
                </div>

                <p class="mt-4 text-center">
                    <a href="{{ route('password.request') }}">Забыли пароль?</a>
                </p>
            </div>
        </div>
    </section>
@endsection
