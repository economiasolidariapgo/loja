<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Support\Texto;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class AccountController extends Controller
{
    // ------------------------------------------------------------------ cadastro
    public function cadastroForm()
    {
        return view('auth', ['mode' => 'cadastro']);
    }

    public function cadastro(Request $r)
    {
        $name = Texto::cut($r->input('name'), 120);
        $ig = ltrim(trim((string) $r->input('instagram')), '@');
        $phone = Texto::cleanPhone($r->input('phone'));
        $email = Texto::cut(strtolower(trim((string) $r->input('email'))), 160);
        $pw = (string) $r->input('password');
        $cat = in_array($r->input('category'), config('shopping.categories'), true) ? $r->input('category') : 'Outros';

        $err = match (true) {
            mb_strlen($name) < 2 => 'Informe o nome da loja.',
            ! preg_match(Store::IG_RE, $ig) => 'Informe o @ do Instagram (letras, números, ponto ou _).',
            $phone === null => 'Informe o telefone comercial com DDD.',
            ! preg_match(Store::EMAIL_RE, $email) => 'Informe um e-mail válido para recuperação de senha.',
            mb_strlen($pw) < 8 || mb_strlen($pw) > 128 => 'A senha precisa ter de 8 a 128 caracteres.',
            $pw !== (string) $r->input('password2') => 'As senhas não conferem.',
            ! $r->filled('consent') => 'É necessário aceitar o uso dos dados para criar a loja.',
            Store::where('email', $email)->exists() => 'Este e-mail já tem uma loja cadastrada. Entre na sua conta.',
            default => null,
        };
        if ($err) {
            return back()->withInput($r->except('password', 'password2'))->with('flash', $err);
        }

        $base = Texto::slugify($name);
        $slug = $base;
        for ($n = 2; Store::where('slug', $slug)->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        try {
            $s = Store::create([
                'email' => $email, 'name' => $name, 'slug' => $slug, 'instagram' => $ig, 'phone' => $phone,
                'category' => $cat, 'password' => $pw, 'consent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return back()->withInput($r->except('password', 'password2'))
                ->with('flash', 'Não foi possível criar a loja. Tente outro e-mail.');
        }

        Auth::login($s);
        $r->session()->regenerate();

        return redirect()->route('painel')->with('flash', 'Loja criada! Complete as informações para aparecer no shopping.');
    }

    // ------------------------------------------------------------------ login / logout
    public function entrarForm()
    {
        return view('auth', ['mode' => 'entrar']);
    }

    public function entrar(Request $r)
    {
        $s = Store::where('email', strtolower(trim((string) $r->input('email'))))->first();

        // Faz o hash mesmo sem loja, para o tempo de resposta não revelar se o e-mail existe
        if ($s) {
            $ok = Hash::check((string) $r->input('password'), $s->password);
        } else {
            Hash::make('x');
            $ok = false;
        }

        if ($s && $ok && $s->is_active) {
            Auth::login($s);
            $r->session()->regenerate();

            return redirect()->route($s->must_change_pw ? 'trocar_senha' : 'painel');
        }

        return back()->withInput($r->only('email'))->with('flash', 'E-mail ou senha incorretos.');
    }

    public function sair(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect()->route('index');
    }

    // ------------------------------------------------------------------ recuperar / redefinir senha
    public function recuperarForm()
    {
        return view('auth', ['mode' => 'recuperar', 'manual' => ! config('shopping.reset_by_email')]);
    }

    public function recuperar(Request $r)
    {
        if (! config('shopping.reset_by_email')) {
            return view('auth', ['mode' => 'recuperar', 'manual' => true]);
        }

        $s = Store::where('email', strtolower(trim((string) $r->input('email'))))->first();
        if ($s) {
            // Link assinado, expira em 1h e vale uma vez só (o "h" muda quando a senha muda)
            $url = URL::temporarySignedRoute('redefinir', now()->addHour(), ['id' => $s->id, 'h' => $this->resetHash($s)]);
            $this->sendMail($s->email, 'Redefinir senha - Shopping Virtual Ponta Grossa',
                "Olá! Para criar uma nova senha acesse (válido por 1 hora):\n{$url}\n\nSe não foi você, ignore este e-mail.");
        }

        return redirect()->route('entrar')
            ->with('flash', 'Se o e-mail estiver cadastrado, enviamos um link para redefinir a senha (válido por 1 hora).');
    }

    public function redefinirForm(Request $r, int $id)
    {
        if (! $this->resetStore($r, $id)) {
            return $this->invalidLink();
        }

        return view('auth', ['mode' => 'redefinir']);
    }

    public function redefinir(Request $r, int $id)
    {
        $s = $this->resetStore($r, $id);
        if (! $s) {
            return $this->invalidLink();
        }

        $pw = (string) $r->input('password');
        if (mb_strlen($pw) < 8 || mb_strlen($pw) > 128 || $pw !== (string) $r->input('password2')) {
            return back()->with('flash', 'A senha precisa ter de 8 a 128 caracteres e ser igual nos dois campos.');
        }

        $s->password = $pw;
        $s->save();

        return redirect()->route('entrar')->with('flash', 'Senha alterada. Entre com a nova senha.');
    }

    // ------------------------------------------------------------------ trocar senha (logada)
    public function trocarSenhaForm()
    {
        if (Auth::user()->must_change_pw) {
            session()->now('flash', 'Você entrou com uma senha temporária. Crie uma nova senha para continuar.');
        }

        return view('auth', ['mode' => 'trocar']);
    }

    public function trocarSenha(Request $r)
    {
        $s = Auth::user();
        $pw = (string) $r->input('password');

        if (mb_strlen($pw) < 8 || mb_strlen($pw) > 128 || $pw !== (string) $r->input('password2')) {
            return back()->with('flash', 'A senha precisa ter de 8 a 128 caracteres e ser igual nos dois campos.');
        }
        if (Hash::check($pw, $s->password)) {
            return back()->with('flash', 'Escolha uma senha diferente da temporária.');
        }

        $s->password = $pw;
        $s->must_change_pw = false;
        $s->save();

        return redirect()->route('painel')->with('flash', 'Senha alterada com sucesso.');
    }

    // ------------------------------------------------------------------ internos
    /** Marca do link de redefinição: muda quando a senha muda (link de uso único). Não expõe o hash real. */
    private function resetHash(Store $s): string
    {
        return substr(hash_hmac('sha256', $s->password, (string) config('app.key')), 0, 16);
    }

    private function resetStore(Request $r, int $id): ?Store
    {
        if (! $r->hasValidSignature()) {      // assinatura + expiração (1h)
            return null;
        }
        $s = Store::find($id);

        return $s && hash_equals($this->resetHash($s), (string) $r->query('h')) ? $s : null;
    }

    private function invalidLink()
    {
        return redirect()->route('recuperar')->with('flash', 'Link inválido ou expirado. Peça um novo.');
    }

    private function sendMail(string $to, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject($subject));
        } catch (\Throwable $e) {
            Log::error('Falha ao enviar e-mail: '.$e->getMessage());
        }
    }
}
