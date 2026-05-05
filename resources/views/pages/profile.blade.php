@extends('layouts.app', ['title' => 'Личный кабинет'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Account</span>
                    <h1 class="page-title">Личный кабинет</h1>
                </div>
            </div>

            <div class="content-grid">
                <article class="panel">
                    <h3>Профиль пользователя</h3>
                    <div class="meta-list">
                        <div><strong>Имя:</strong> {{ auth()->user()->name }}</div>
                        <div><strong>Email:</strong> {{ auth()->user()->email }}</div>
                    </div>
                </article>

                <aside class="panel">
                    <h3>Действия</h3>
                    <p>Здесь собрана базовая информация по вашему аккаунту.</p>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">Выйти из аккаунта</button>
                    </form>
                </aside>
            </div>
        </div>
    </section>
@endsection
