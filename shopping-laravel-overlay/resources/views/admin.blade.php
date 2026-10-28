@extends('base')
@section('content')
<div class="wrap">
<h1>Banners</h1>
<p class="muted">Cada envio adiciona <b>um</b> banner novo; repita o formulário para cadastrar quantos quiser. Banners do <b>carrossel do topo</b> (vertical, ex. 900×1200) aparecem lado a lado e centralizados quando são poucos. A <b>faixa larga</b> (ex. 1600×300) aparece no meio da página. Use "Ordem" (menor primeiro) para organizar.</p>
<form method="post" action="{{ route('admin_banner_add') }}" enctype="multipart/form-data" class="panel" id="banners">
  @csrf
  <div class="cols">
    <label>Imagem <span class="hint">(topo: vertical 3:4, ex. 900×1200 · faixa: larga, ex. 1600×300)</span><input type="file" name="image" accept="image/png,image/jpeg,image/webp" required></label>
    <label>Posição<select name="posicao"><option value="topo">Carrossel do topo</option><option value="faixa">Faixa larga</option></select></label>
    <label>Link ao clicar <span class="hint">(opcional)</span><input name="link" placeholder="https://… ou /cadastro"></label>
    <label>Texto alternativo<input name="alt" maxlength="120"></label>
    <label>Ordem<input type="number" name="sort" value="0"></label>
  </div>
  <button class="btn">Publicar banner</button>
</form>
<table class="tbl"><tr><th></th><th>Posição</th><th>Link</th><th>Status</th><th></th></tr>
@foreach ($banners as $b)<tr><td><img src="{{ route('midia', $b->image) }}" alt="" width="60"></td><td>{{ $b->posicao }}</td><td>{{ $b->link }}</td><td>{{ $b->is_active ? 'Ativo' : 'Oculto' }}</td>
<td><form method="post" action="{{ route('admin_banner', ['bid' => $b->id, 'acao' => 'alternar']) }}">@csrf<button class="btn sm ghost">{{ $b->is_active ? 'Ocultar' : 'Ativar' }}</button></form>
<form method="post" action="{{ route('admin_banner', ['bid' => $b->id, 'acao' => 'excluir']) }}">@csrf<button class="btn sm ghost">Excluir</button></form></td></tr>@endforeach</table>

<h1>Lojas cadastradas ({{ $stores->count() }})</h1>
<table class="tbl"><tr><th>Loja</th><th>Instagram</th><th>Telefone</th><th>E-mail</th><th>Produtos</th><th>Status</th><th></th><th></th></tr>
@foreach ($stores as $s)<tr><td><a href="{{ route('loja', $s->slug) }}">{{ $s->name }}</a></td><td>{{ '@'.$s->instagram }}</td><td>{{ $s->phone_fmt }}</td><td>{{ $s->email }}</td><td>{{ $s->products_count }}</td>
<td>{{ $s->is_active ? 'Ativa' : 'Oculta' }}</td>
<td><form method="post" action="{{ route('admin_toggle', $s->id) }}">@csrf<button class="btn sm ghost">{{ $s->is_active ? 'Ocultar' : 'Ativar' }}</button></form></td>
<td>@if (! $s->is_admin)<form method="post" action="{{ route('admin_temp_pw', $s->id) }}">@csrf<button class="btn sm">Senha temporária</button></form>@endif</td></tr>@endforeach
</table></div>
@endsection
