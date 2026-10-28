@extends('base')
@section('content')
<div class="wrap">
  <div class="between"><h1>Minha loja</h1><a class="btn sm" href="{{ route('loja', $s->slug) }}">Ver minha página</a></div>
  <form method="post" action="{{ route('painel') }}" enctype="multipart/form-data" class="panel">@csrf
    <h2>Informações</h2>
    <div class="cols">
      <label>Nome<input name="name" value="{{ $s->name }}" required></label>
      <label>Categoria<select name="category">@foreach ($categories as $c)<option @selected($c === $s->category)>{{ $c }}</option>@endforeach</select></label>
      <label>WhatsApp (opcional, se diferente)<input name="whatsapp" value="{{ $s->whatsapp_fmt }}" placeholder="42 99999-9999"></label>
      <label>Telefone comercial<input name="phone" value="{{ $s->phone_fmt }}" required></label>
      <label>Endereço<input name="address" value="{{ $s->address }}" placeholder="Rua, número, bairro"></label>
      <label>@ do Instagram<input name="instagram" value="{{ '@'.$s->instagram }}" required></label>
      <label>Abre às<input type="time" name="open_time" value="{{ $s->open_time }}"></label>
      <label>Fecha às<input type="time" name="close_time" value="{{ $s->close_time }}"></label>
    </div>
    <fieldset><legend>Dias de atendimento</legend>
      @foreach ($days as $d)<label class="chk"><input type="checkbox" name="open_days[]" value="{{ $loop->index }}" @checked(in_array((string) $loop->index, $s->days_list, true))> {{ $d }}</label>@endforeach
    </fieldset>
    <label>Sobre a loja<textarea name="description" rows="3" maxlength="400">{{ $s->description }}</textarea></label>
    <label>Logo (JPG, PNG ou WebP)<input type="file" name="logo" accept="image/*"></label>
    <button class="btn">Salvar alterações</button>
  </form>

  <form method="post" action="{{ route('add_produto') }}" enctype="multipart/form-data" class="panel" id="produtos">@csrf
    <h2>Adicionar produto à vitrine</h2>
    <div class="cols">
      <label>Nome do produto<input name="name" required></label>
      <label>Preço<input name="price" placeholder="R$ 49,90"></label>
    </div>
    <label>Descrição curta<input name="description" maxlength="300"></label>
    <label>Foto<input type="file" name="image" accept="image/*"></label>
    <button class="btn">Publicar produto</button>
  </form>

  <h2>Produtos publicados ({{ $s->products->count() }})</h2>
  @if ($s->products->isNotEmpty())<div class="grid">@foreach ($s->products as $p)
    <div class="prod"><div class="ph">@if ($p->image)<img src="{{ route('midia', $p->image) }}" alt="">@endif</div>
      <h4>{{ $p->name }}</h4><b>{{ $p->price }}</b>
      <form method="post" action="{{ route('del_produto', $p->id) }}">@csrf<button class="btn sm ghost">Remover</button></form></div>
  @endforeach</div>
  @else<p class="empty">Sua vitrine está vazia. Publique o primeiro produto acima.</p>@endif
</div>
@endsection
