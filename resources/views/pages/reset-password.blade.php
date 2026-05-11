@extends('layouts.app', ['title' => 'Новый пароль'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="max-w-md mx-auto">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Account</span>
                        <h1>Новый пароль</h1>
                    </div>
                </div>

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
                    <form method="POST" action="{{ route('password.update') }}" class="form-grid">
                        @csrf

                        <input type="hidden" name="token" value="{{ $token }}">

                        <label>
                            Email
                            <input
                                class="w-full border p-2 rounded"
                                name="email"
                                type="email"
                                value="{{ old('email', $email) }}"
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

                        <label>
                            Повторите пароль
                            <input
                                class="w-full border p-2 rounded"
                                name="password_confirmation"
                                type="password"
                                placeholder="Password"
                                required
                            >
                        </label>

                        <button class="w-full bg-black text-white p-2 rounded" type="submit">
                            Сохранить пароль
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
