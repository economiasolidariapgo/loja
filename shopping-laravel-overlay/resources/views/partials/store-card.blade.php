<a class="store" href="{{ route('loja', $s->slug) }}">
  <div class="cover">@if ($s->logo)<img src="{{ route('midia', $s->logo) }}" alt="">@else{{ $s->initial }}@endif
    <div class="badges"><span class="badge">{{ $s->category }}</span><span class="badge {{ $s->open_now ? 'on' : 'lt' }}">{{ $s->open_now ? 'Aberto agora' : 'Fechado' }}</span></div></div>
  <div class="sbody"><small>Loja · Ponta Grossa</small><h3>{{ $s->name }}</h3><small>{{ $s->hours_text }}</small><hr>
    <small>Instagram</small><b>{{ '@'.$s->instagram }}</b><span class="btn block">Saiba mais</span></div>
</a>
