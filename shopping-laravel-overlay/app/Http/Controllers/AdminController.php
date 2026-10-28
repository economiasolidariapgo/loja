<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Media;
use App\Models\Store;
use App\Support\ImageStore;
use App\Support\Texto;
use Illuminate\Http\Request;

// Acesso restrito pelo middleware "admin" (ver routes/web.php)
class AdminController extends Controller
{
    public function index()
    {
        return view('admin', [
            'stores' => Store::withCount('products')->orderByDesc('created_at')->get(),
            'banners' => Banner::orderBy('posicao')->orderBy('sort')->orderByDesc('id')->get(),
        ]);
    }

    public function toggle(int $sid)
    {
        $s = Store::findOrFail($sid);
        $s->is_active = ! $s->is_active;
        $s->save();

        return redirect()->route('admin');
    }

    public function tempPw(int $sid)
    {
        $s = Store::findOrFail($sid);
        if ($s->is_admin) {
            return redirect()->route('admin')->with('flash', 'Não é possível gerar senha temporária para outro administrador.');
        }

        $tmp = rtrim(strtr(base64_encode(random_bytes(9)), '+/', '-_'), '=');   // 12 caracteres, como token_urlsafe(9)
        $s->password = $tmp;
        $s->must_change_pw = true;
        $s->save();

        return redirect()->route('admin')->with('flash',
            "Senha temporária de {$s->name} ({$s->email}): {$tmp}  — copie agora, ela não aparece de novo. "
            .'A pessoa será obrigada a trocá-la no primeiro acesso.');
    }

    public function bannerAdd(Request $r)
    {
        $link = trim((string) $r->input('link'));
        $img = ImageStore::save($r->file('image'), 1600);

        if (! $img) {
            session()->flash('flash', 'Envie uma imagem JPG, PNG ou WebP válida.');
        } elseif ($link !== '' && ! Texto::safeLink($link)) {
            Media::where('id', $img)->delete();   // não deixa imagem órfã
            session()->flash('flash', 'Link inválido: use http(s):// ou um caminho do site.');
        } else {
            $sort = trim((string) $r->input('sort'));
            Banner::create([
                'image' => $img,
                'link' => $link,
                'alt' => Texto::cut($r->input('alt'), 120),
                'posicao' => $r->input('posicao') === 'faixa' ? 'faixa' : 'topo',
                'sort' => preg_match('/^-?\d+\z/', $sort) ? (int) $sort : 0,
            ]);
            session()->flash('flash', 'Banner publicado.');
        }

        return redirect(route('admin').'#banners');
    }

    public function banner(int $bid, string $acao)
    {
        $b = Banner::findOrFail($bid);

        if ($acao === 'excluir') {
            Media::where('id', $b->image)->delete();
            $b->delete();
        } else {                       // "alternar" (a rota só aceita esses dois)
            $b->is_active = ! $b->is_active;
            $b->save();
        }

        return redirect(route('admin').'#banners');
    }
}
