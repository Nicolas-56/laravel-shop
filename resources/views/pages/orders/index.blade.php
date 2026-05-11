@extends('layouts.app', ['title' => 'Мои заказы'])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Account</span>
                    <h1 class="page-title">Мои заказы</h1>
                </div>
            </div>

            <div class="content-grid">
                @forelse ($orders as $order)
                    <article class="panel">
                        <h3>Заказ #{{ $order->id }}</h3>
                        <div class="meta-list">
                            <div><strong>Статус:</strong> {{ $order->status }}</div>
                            <div><strong>Позиций:</strong> {{ $order->items_count }}</div>
                            <div><strong>Сумма:</strong> {{ $order->total }} руб.</div>
                        </div>
                        <a href="{{ route('orders.show', $order) }}">Открыть заказ</a>
                    </article>
                @empty
                    <article class="panel">
                        <h3>Заказов пока нет</h3>
                        <p>После оформления заказа он появится здесь.</p>
                    </article>
                @endforelse
            </div>
        </div>
    </section>
@endsection
