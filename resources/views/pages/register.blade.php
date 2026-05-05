@extends('layouts.app', ['title' => 'Регистрация'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="max-w-md mx-auto">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Account</span>
                        <h1>Создать аккаунт</h1>
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
                    <form method="POST" action="{{ url('/register') }}" class="form-grid">
                        @csrf

                        <label>
                            Имя
                            <input
                                class="w-full border p-2 rounded"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Name"
                                required
                            >
                            @error('name') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

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
                            @error('email') <span class="error-text">{{ $message }}</span> @enderror
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
                            @error('password') <span class="error-text">{{ $message }}</span> @enderror
                        </label>

                        <button class="w-full bg-black text-white p-2 rounded" type="submit">
                            Зарегистрироваться
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
