<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Shopping Virtual Ponta Grossa')</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/style.css') }}">
</head>
<body>
<header class="top"><div class="wrap bar">
  <a href="{{ route('index') }}" class="brand">Shopping Virtual <span>Ponta Grossa</span></a>
  <nav>
    @auth
      @if (auth()->user()->is_admin)<a href="{{ route('admin') }}">Admin</a>@endif
      <a href="{{ route('painel') }}">Minha loja</a>
      <form method="post" action="{{ route('sair') }}">@csrf<button class="lnk">Sair</button></form>
    @else
      <a href="{{ route('entrar') }}">Entrar</a><a class="btn sm" href="{{ route('cadastro') }}">Abrir minha loja</a>
    @endauth
  </nav></div></header>
@if (session('flash'))<div class="wrap"><p class="flash">{{ session('flash') }}</p></div>@endif
<main>@yield('content')</main>
<footer><div class="wrap">Shopping Virtual Ponta Grossa · O comércio da cidade num só lugar</div></footer>
@if ($siteWa)<a class="wa" href="https://wa.me/55{{ $siteWa }}" target="_blank" rel="noopener" aria-label="Fale conosco no WhatsApp">💬</a>@endif
<script src="{{ asset('assets/app.js') }}" defer></script>
</body></html>
