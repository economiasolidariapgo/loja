<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * A loja É o usuário autenticável (como no Flask: Store(UserMixin)).
 * Requer AUTH_MODEL=App\Models\Store no .env (ou ajuste em config/auth.php).
 */
class Store extends Authenticatable
{
    public const IG_RE = '/^[A-Za-z0-9._]{1,30}\z/';
    public const EMAIL_RE = '/^[^@\s]{1,64}@[^@\s]+\.[^@\s]{2,}\z/';

    // Sempre preenchido com dados já validados nos controllers (nunca com $request->all()).
    protected $guarded = [];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',          // atribuir texto puro já grava o hash
            'is_active' => 'boolean',
            'must_change_pw' => 'boolean',
            'consent_at' => 'datetime',
        ];
    }

    // Sem "lembrar de mim": desativa o remember_token.
    public function getRememberTokenName(): string
    {
        return '';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderByDesc('id');
    }

    // ------------------------------------------------------------ atributos derivados
    protected function isAdmin(): Attribute
    {
        return Attribute::get(fn () => in_array(strtolower($this->email), config('shopping.admins'), true));
    }

    protected function initial(): Attribute
    {
        return Attribute::get(fn () => mb_strtoupper(mb_substr($this->name, 0, 1)));
    }

    /** @return string[] ex.: ["0","1","2"] (0 = segunda) */
    protected function daysList(): Attribute
    {
        return Attribute::get(fn () => array_values(array_filter(explode(',', (string) $this->open_days), 'strlen')));
    }

    protected function hoursText(): Attribute
    {
        return Attribute::get(function () {
            $ds = array_map('intval', $this->days_list);
            if (! $ds) {
                return 'Horário não informado';
            }
            sort($ds);
            $names = config('shopping.days');
            $last = end($ds);

            if (count($ds) === 1) {
                $span = $names[$ds[0]];
            } elseif ($ds === range($ds[0], $last)) {
                $span = "{$names[$ds[0]]} a {$names[$last]}";
            } else {
                $span = implode(', ', array_map(fn ($d) => $names[$d], $ds));
            }

            return "{$span}, {$this->open_time} às {$this->close_time}";
        });
    }

    protected function openNow(): Attribute
    {
        return Attribute::get(function () {
            $n = Carbon::now(config('shopping.timezone'));

            return in_array((string) ($n->dayOfWeekIso - 1), $this->days_list, true)
                && $this->open_time <= $n->format('H:i') && $n->format('H:i') < $this->close_time;
        });
    }

    protected function phoneFmt(): Attribute
    {
        return Attribute::get(fn () => self::fone($this->phone));
    }

    protected function whatsappFmt(): Attribute
    {
        return Attribute::get(fn () => self::fone($this->whatsapp));
    }

    // ------------------------------------------------------------ formatação / links
    /** (42) 99999-9999 — era o filtro `fone` do Jinja */
    public static function fone(?string $v): string
    {
        $v = (string) $v;

        return match (strlen($v)) {
            11 => sprintf('(%s) %s-%s', substr($v, 0, 2), substr($v, 2, 5), substr($v, 7)),
            10 => sprintf('(%s) %s-%s', substr($v, 0, 2), substr($v, 2, 4), substr($v, 6)),
            default => $v,
        };
    }

    /** Link wa.me da loja (ou de um produto). Era o filtro `wa` do Jinja. */
    public function waLink(?Product $product = null): string
    {
        $num = (string) preg_replace('/\D/', '', $this->whatsapp ?: ($this->phone ?: ''));
        if ($num === '') {
            return '';
        }
        // Os números guardados têm 10/11 dígitos (sem país). Só não prefixa se já vier com 55 + DDD + número.
        if (! (str_starts_with($num, '55') && in_array(strlen($num), [12, 13], true))) {
            $num = '55'.$num;
        }
        $what = $product ? "o produto {$product->name} " : 'a loja ';

        return 'https://wa.me/'.$num.'?text='.rawurlencode("Olá! Vi {$what}no Shopping Virtual Ponta Grossa.");
    }
}
