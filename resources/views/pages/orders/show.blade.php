@extends('layouts.app', ['title' => 'Заказ #' . $order->id])

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="section-heading">
                <div>
                    <span class="eyebrow">Order</span>
                    <h1 class="page-title">Заказ #{{ $order->id }}</h1>
                </div>
            </div>

            <div class="content-grid">
                <article class="panel">
                    <h3>Данные заказа</h3>
                    <div class="meta-list">
                        <div><strong>Статус:</strong> {{ $order->status }}</div>
                        <div><strong>Имя:</strong> {{ $order->customer_name }}</div>
                        <div><strong>Телефон:</strong> {{ $order->customer_phone }}</div>
                        @if ($order->customer_email)
                            <div><strong>Email:</strong> {{ $order->customer_email }}</div>
                        @endif
                        <div><strong>Сумма:</strong> {{ $order->total }} руб.</div>
                    </div>
                </article>

                <article class="panel">
                    <h3>Позиции</h3>
                    <div class="meta-list">
                        @foreach ($order->items as $item)
                            <div>
                                <strong>{{ $item->product_name }}</strong>
                                {{ $item->quantity }} x {{ $item->product_price }} руб. = {{ $item->line_total }} руб.
                            </div>
                        @endforeach
                    </div>
                </article>
            </div>
        </div>
    </section>
@endsection
