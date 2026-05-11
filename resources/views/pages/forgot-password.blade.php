@extends('layouts.app', ['title' => 'Восстановление пароля'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="max-w-md mx-auto">
                <div class="section-heading">
                    <div>
                        <span class="eyebrow">Account</span>
                        <h1>Восстановление пароля</h1>
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
                    <form method="POST" action="{{ route('password.email') }}" class="form-grid">
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

                        <button class="w-full bg-black text-white p-2 rounded" type="submit">
                            Отправить ссылку
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
