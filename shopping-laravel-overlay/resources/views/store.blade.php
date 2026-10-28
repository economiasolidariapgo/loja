@extends('base')
@section('title', $s->name.' · Shopping Virtual Ponta Grossa')
@section('content')
<section class="storehead"><div class="wrap sh">
  <div class="logo big">@if ($s->logo)<img src="{{ route('midia', $s->logo) }}" alt="">@else{{ $s->initial }}@endif</div>
  <div><h1>{{ $s->name }}</h1><p><span class="badge">{{ $s->category }}</span></p>
    <span class="pill {{ $s->open_now ? 'on' : '' }}">{{ $s->open_now ? 'Aberto agora' : 'Fechado no momento' }}</span></div>
</div></section>
<div class="wrap two">
  <aside class="info">
    @if ($s->description)<p>{{ $s->description }}</p>@endif
    <p><b>Horário</b><br>{{ $s->hours_text }}</p>
    @if ($s->address)<p><b>Endereço</b><br>{{ $s->address }}, Ponta Grossa</p>@endif
    <p><b>Telefone comercial</b><br><a href="tel:+55{{ $s->phone }}">{{ $s->phone_fmt }}</a></p>
    <p><b>Instagram</b><br><a href="https://instagram.com/{{ $s->instagram }}" target="_blank" rel="noopener noreferrer">{{ '@'.$s->instagram }}</a></p>
    @if ($s->waLink())<a class="btn" href="{{ $s->waLink() }}" target="_blank" rel="noopener">Falar no WhatsApp</a>@endif
  </aside>
  <section>
    <h2>Vitrine</h2>
    @if ($s->products->isNotEmpty())<div class="grid">@foreach ($s->products as $p)
      <div class="prod"><div class="ph">@if ($p->image)<img src="{{ route('midia', $p->image) }}" alt="{{ $p->name }}">@endif</div>
        <h4>{{ $p->name }}</h4>@if ($p->description)<p class="muted">{{ $p->description }}</p>@endif<b>{{ $p->price }}</b>
        @if ($s->waLink())<a class="btn sm" href="{{ $s->waLink($p) }}" target="_blank" rel="noopener">Pedir pelo WhatsApp</a>@endif</div>
    @endforeach</div>
    @else<p class="empty">Esta loja ainda não publicou produtos.</p>@endif
  </section>
</div>
@endsection
