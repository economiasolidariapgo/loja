@extends('base')
@section('content')
<section class="hero"><div class="wrap">
  <h1>Encontre o melhor do comércio de Ponta Grossa</h1>
  <p>Veja produtos, horários e fale direto com o lojista pelo WhatsApp.</p>
  <form action="{{ route('index') }}#lojas" class="pill-search">
    <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
    <input name="q" value="{{ $q }}" placeholder="Busque uma loja ou produto" aria-label="Buscar">
  </form>
</div></section>

<section class="banners">
  @if (auth()->check() && auth()->user()->is_admin)<a class="btn sm manage" href="{{ route('admin') }}#banners">+ Gerenciar banners</a>@endif
  <button class="arrow l" data-scroll="-1" aria-label="Anterior">←</button>
  <div class="track" id="track">
    @forelse ($topo as $b)
      <a class="bn" @if ($b->link) href="{{ $b->link }}" @if (str_starts_with($b->link, 'http')) target="_blank" rel="noopener noreferrer" @endif @endif><img src="{{ route('midia', $b->image) }}" alt="{{ $b->alt }}"></a>
    @empty
      <a class="bn ph1" href="{{ route('cadastro') }}"><b>Abra sua loja</b><small>Grátis. Apareça para toda a cidade.</small></a>
      <a class="bn ph2" href="{{ route('cadastro') }}"><b>Seu banner aqui</b><small>Espaço para campanhas e promoções.</small></a>
      <a class="bn ph3" href="{{ route('cadastro') }}"><b>Ofertas da semana</b><small>Destaque seus produtos.</small></a>
    @endforelse
  </div>
  <button class="arrow r" data-scroll="1" aria-label="Próximo">→</button>
</section>

<div class="wrap">
  @if ($latest->isNotEmpty())<h2 class="big">Novidades nas vitrines</h2>
  <div class="grid">@foreach ($latest as $p)
    <a class="prod" href="{{ route('loja', $p->store->slug) }}">
      <div class="ph">@if ($p->image)<img src="{{ route('midia', $p->image) }}" alt="{{ $p->name }}">@endif</div>
      <h4>{{ $p->name }}</h4><span class="muted">{{ $p->store->name }}</span><b>{{ $p->price }}</b></a>
  @endforeach</div>
  @endif

  @if ($faixa->isNotEmpty())
    @php $b = $faixa->first(); @endphp
    <a class="faixa" @if ($b->link) href="{{ $b->link }}" @endif><img src="{{ route('midia', $b->image) }}" alt="{{ $b->alt }}"></a>
  @else
    <a class="faixa ph" href="{{ route('cadastro') }}">Anuncie aqui: faixa de banner</a>
  @endif

  <h2 class="big" id="lojas">Confira todas as lojas</h2>
  <form class="catalog" id="filtros" method="get" action="{{ route('index') }}#lojas">
    <aside class="filters">
      <h3>Filtrar por</h3>
      <a class="clear" href="{{ route('index') }}#lojas">Limpar filtros</a>
      <h4>Situação</h4>
      <label class="chk"><input type="checkbox" name="abertas" value="1" @checked($onlyOpen)> Aberto agora</label>
      <h4>Categoria</h4>
      @foreach ($categories as $c)<label class="chk"><input type="checkbox" name="categoria[]" value="{{ $c }}" @checked(in_array($c, $cats, true))> {{ $c }}</label>@endforeach
    </aside>
    <section>
      <div class="topbar">
        <div class="pill-search"><input name="q" value="{{ $q }}" placeholder="Busque pelo nome da loja ou produto" aria-label="Buscar"></div>
        <b>{{ $stores->count() }} loja{{ $stores->count() === 1 ? '' : 's' }} encontrada{{ $stores->count() === 1 ? '' : 's' }}</b>
        <select name="ordem" aria-label="Ordenar por"><option value="nome" @selected($order === 'nome')>Ordenar: A–Z</option><option value="recentes" @selected($order === 'recentes')>Mais recentes</option></select>
      </div>
      @if ($stores->isNotEmpty())<div class="stores">@foreach ($stores as $s)@include('partials.store-card', ['s' => $s])@endforeach</div>
      @else<p class="empty">Nenhuma loja encontrada. Tente outro filtro ou <a href="{{ route('cadastro') }}">abra a sua loja</a>.</p>@endif
    </section>
  </form>
</div>
@endsection
