<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Media;
use App\Models\Product;
use App\Models\Store;
use App\Support\Texto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function saude()
    {
        return response('ok', 200, ['Cache-Control' => 'no-store']);   // não consulta o banco
    }

    public function index(Request $r)
    {
        $q = Texto::cut((string) $r->query('q', ''), 80);
        $cats = array_values(array_intersect(
            array_filter((array) $r->query('categoria', []), 'is_string'),
            config('shopping.categories')
        ));
        $onlyOpen = $r->query('abertas') === '1';
        $order = (string) $r->query('ordem', 'nome');

        $stores = Store::query()->where('is_active', true);
        if ($cats) {
            $stores->whereIn('category', $cats);
        }
        if ($q !== '') {
            $op = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';   // busca sem diferenciar maiúsculas
            $like = "%{$q}%";
            $stores->where(fn ($w) => $w
                ->where('name', $op, $like)
                ->orWhere('description', $op, $like)
                ->orWhereIn('id', Product::select('store_id')->where('name', $op, $like)));
        }
        $stores = $order === 'recentes' ? $stores->orderByDesc('created_at') : $stores->orderBy('name');
        $stores = $stores->get();
        if ($onlyOpen) {
            $stores = $stores->filter(fn ($s) => $s->open_now)->values();
        }

        $filtering = $q !== '' || $cats || $onlyOpen;
        $latest = $filtering ? collect() : Product::with('store')
            ->whereHas('store', fn ($s) => $s->where('is_active', true))
            ->orderByDesc('id')->limit(8)->get();

        $banners = Banner::where('is_active', true)->orderBy('sort')->orderByDesc('id')->get();

        return view('index', [
            'stores' => $stores, 'latest' => $latest, 'q' => $q, 'cats' => $cats,
            'onlyOpen' => $onlyOpen, 'order' => $order,
            'topo' => $banners->where('posicao', 'topo')->values(),
            'faixa' => $banners->where('posicao', 'faixa')->values(),
        ]);
    }

    public function loja(string $slug)
    {
        $s = Store::where('slug', $slug)->firstOrFail();

        // Loja oculta só aparece para o próprio dono e para admins
        if (! $s->is_active && ! (Auth::check() && (Auth::id() === $s->id || Auth::user()->is_admin))) {
            abort(404);
        }

        return view('store', ['s' => $s->load('products')]);
    }

    public function midia(string $name)
    {
        $m = Media::find($name);
        abort_if(! $m, 404);

        return response($m->data, 200, [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
