<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Product;
use App\Models\Store;
use App\Support\ImageStore;
use App\Support\Texto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PainelController extends Controller
{
    public function index()
    {
        $s = Auth::user();

        return view('painel', ['s' => $s->load('products')]);
    }

    public function update(Request $r)
    {
        $s = Auth::user();
        $name = Texto::cut($r->input('name'), 120);
        $ig = ltrim(trim((string) $r->input('instagram')), '@');
        $phone = Texto::cleanPhone($r->input('phone'));

        if (mb_strlen($name) < 2 || ! preg_match(Store::IG_RE, $ig) || ! $phone) {
            return redirect()->route('painel')->with('flash', 'Nome, @ do Instagram e telefone comercial válidos são obrigatórios.');
        }

        $time = fn (string $key, string $default) => preg_match('/^\d\d:\d\d\z/', (string) $r->input($key)) ? $r->input($key) : $default;
        $days = array_values(array_intersect(array_filter((array) $r->input('open_days', []), 'is_string'), ['0', '1', '2', '3', '4', '5', '6']));

        $s->name = $name;
        $s->instagram = $ig;
        $s->phone = $phone;
        $s->category = in_array($r->input('category'), config('shopping.categories'), true) ? $r->input('category') : 'Outros';
        $s->description = Texto::cut($r->input('description'), 400);
        $s->address = Texto::cut($r->input('address'), 200);
        $s->whatsapp = Texto::cleanPhone($r->input('whatsapp')) ?? '';
        $s->open_time = $time('open_time', '09:00');
        $s->close_time = $time('close_time', '18:00');
        $s->open_days = implode(',', $days);

        $logo = ImageStore::save($r->file('logo'), 600);
        if ($logo) {
            if ($s->logo) {
                Media::where('id', $s->logo)->delete();
            }
            $s->logo = $logo;
        }
        $s->save();

        return redirect()->route('painel')->with('flash', 'Alterações salvas.');
    }

    public function addProduto(Request $r)
    {
        $name = Texto::cut($r->input('name'), 120);
        if ($name !== '') {
            Product::create([
                'store_id' => Auth::id(),
                'name' => $name,
                'price' => Texto::cut($r->input('price'), 30),
                'description' => Texto::cut($r->input('description'), 300),
                'image' => ImageStore::save($r->file('image')),
            ]);
            session()->flash('flash', 'Produto publicado na sua vitrine.');
        }

        return redirect(route('painel').'#produtos');
    }

    public function delProduto(int $pid)
    {
        $p = Product::find($pid);
        if ($p && $p->store_id === Auth::id()) {
            if ($p->image) {
                Media::where('id', $p->image)->delete();
            }
            $p->delete();
            session()->flash('flash', 'Produto removido.');
        }

        return redirect(route('painel').'#produtos');
    }
}
