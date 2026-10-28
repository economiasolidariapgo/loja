@extends('base')
@section('content')
@php
  $titles = ['cadastro' => 'Abra sua loja no shopping', 'entrar' => 'Entrar na minha loja', 'recuperar' => 'Recuperar senha',
             'redefinir' => 'Criar nova senha', 'trocar' => 'Crie sua nova senha'];
  $buttons = ['cadastro' => 'Criar loja', 'entrar' => 'Entrar', 'recuperar' => 'Enviar link de recuperação',
              'redefinir' => 'Salvar nova senha', 'trocar' => 'Salvar nova senha'];
@endphp
<div class="wrap narrow"><form method="post" class="panel" autocomplete="on" @if ($mode === 'redefinir') action="{{ request()->fullUrl() }}" @endif>
  @csrf
  <h1>{{ $titles[$mode] }}</h1>
  @if (! empty($manual))
    <p>A recuperação de senha é feita pelo administrador do shopping. Entre em contato informando o nome da sua loja e o seu @ do Instagram; você receberá uma senha temporária e criará uma nova no primeiro acesso.</p>
    @if ($siteWa)<a class="btn block" href="https://wa.me/55{{ $siteWa }}" target="_blank" rel="noopener">Falar com o administrador</a>@endif
    <p class="muted"><a href="{{ route('entrar') }}">Voltar para entrar</a></p>
  @else
    @if ($mode === 'cadastro')
    <label>Nome da loja<input name="name" value="{{ old('name') }}" required maxlength="120"></label>
    <label>@ do Instagram<input name="instagram" value="{{ old('instagram') }}" placeholder="&#64;sualoja" required maxlength="31" pattern="@?[A-Za-z0-9._]{1,30}"></label>
    <label>Telefone comercial (com DDD)<input name="phone" type="tel" value="{{ old('phone') }}" placeholder="(42) 3222-0000" required inputmode="tel"></label>
    <label>Categoria<select name="category">@foreach ($categories as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select></label>
    @endif
    @if (in_array($mode, ['cadastro', 'entrar', 'recuperar']))
    <label>E-mail @if ($mode === 'cadastro')<span class="hint">(usado para entrar e para recuperar a senha)</span>@endif<input type="email" name="email" value="{{ old('email') }}" required maxlength="160" autocomplete="email"></label>
    @endif
    @if ($mode !== 'recuperar')
    <label>{{ in_array($mode, ['redefinir', 'trocar']) ? 'Nova senha' : 'Senha' }}@if ($mode !== 'entrar') <span class="hint">(mínimo 8 caracteres)</span>@endif<input type="password" name="password" required minlength="{{ $mode === 'entrar' ? 1 : 8 }}" maxlength="128" autocomplete="{{ $mode === 'entrar' ? 'current-password' : 'new-password' }}"></label>
    @endif
    @if (in_array($mode, ['cadastro', 'redefinir', 'trocar']))<label>Repita a senha<input type="password" name="password2" required minlength="8" maxlength="128" autocomplete="new-password"></label>@endif
    @if ($mode === 'cadastro')<label class="consent"><input type="checkbox" name="consent" value="1" required> <span>Concordo que o Shopping Virtual use meus dados (Instagram, telefone e e-mail) para exibir minha loja e permitir o contato com clientes e a recuperação da conta.</span></label>@endif
    <button class="btn block">{{ $buttons[$mode] }}</button>
    <p class="muted">
      @if ($mode === 'cadastro')Já tem loja? <a href="{{ route('entrar') }}">Entrar</a>
      @elseif ($mode === 'entrar')<a href="{{ route('recuperar') }}">Esqueci minha senha</a> · <a href="{{ route('cadastro') }}">Abrir minha loja</a>
      @else<a href="{{ route('entrar') }}">Voltar para entrar</a>@endif</p>
  @endif
</form></div>
@endsection
