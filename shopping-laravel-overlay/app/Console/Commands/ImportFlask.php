<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;

/**
 * Importa o banco SQLite do projeto Flask (instance/shopping.db) para o banco do Laravel.
 *
 * Atenção às senhas: o Flask usava hash scrypt (werkzeug), que o PHP não verifica. Por isso as lojas
 * importadas recebem uma senha aleatória + "trocar senha no próximo acesso". Para cada lojista voltar a
 * entrar: use "Esqueci minha senha" (se o e-mail estiver configurado) ou, em /admin, "Senha temporária".
 */
class ImportFlask extends Command
{
    protected $signature = 'shopping:import-flask
                            {arquivo : Caminho do shopping.db (SQLite) do projeto Flask}
                            {--force : Importa mesmo que já existam lojas neste banco}';

    protected $description = 'Importa lojas, produtos, banners e imagens do banco SQLite do projeto Flask';

    public function handle(): int
    {
        $file = (string) $this->argument('arquivo');
        if (! is_file($file)) {
            $this->error("Arquivo não encontrado: {$file}");

            return self::FAILURE;
        }
        if (DB::table('stores')->exists() && ! $this->option('force')) {
            $this->error('Já existem lojas neste banco. Use --force para importar mesmo assim.');

            return self::FAILURE;
        }

        $src = new PDO('sqlite:'.$file, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $now = now()->toDateTimeString();
        $n = ['stores' => 0, 'products' => 0, 'media' => 0, 'banners' => 0];

        DB::transaction(function () use ($src, $now, &$n) {
            foreach ($src->query('SELECT * FROM store') as $r) {
                $created = $r['created'] ?? null ?: $now;
                DB::table('stores')->insert([
                    'id' => $r['id'],
                    'email' => strtolower($r['email']),
                    'password' => Hash::make(Str::random(40)),
                    'name' => $r['name'],
                    'slug' => $r['slug'],
                    'instagram' => $r['instagram'],
                    'phone' => $r['phone'],
                    'category' => $r['category'] ?: 'Outros',
                    'description' => $r['description'] ?? null,
                    'whatsapp' => $r['whatsapp'] ?? '',
                    'address' => $r['address'] ?? '',
                    'logo' => $r['logo'] ?: null,
                    'open_time' => $r['open_time'] ?: '09:00',
                    'close_time' => $r['close_time'] ?: '18:00',
                    'open_days' => $r['open_days'] ?? '',
                    'is_active' => (int) $r['is_active'],
                    'must_change_pw' => 1,
                    'consent_at' => $r['consent_at'] ?: null,
                    'created_at' => $created,
                    'updated_at' => $created,
                ]);
                $n['stores']++;
            }

            foreach ($src->query('SELECT * FROM product') as $r) {
                DB::table('products')->insert([
                    'id' => $r['id'], 'store_id' => $r['store_id'], 'name' => $r['name'],
                    'price' => $r['price'] ?? '', 'description' => $r['description'] ?? '', 'image' => $r['image'] ?: null,
                ]);
                $n['products']++;
            }

            foreach ($src->query('SELECT * FROM media') as $r) {   // `data` já é base64, igual ao formato novo
                DB::table('media')->insert(['id' => $r['id'], 'data' => $r['data']]);
                $n['media']++;
            }

            foreach ($src->query('SELECT * FROM banner') as $r) {
                DB::table('banners')->insert([
                    'id' => $r['id'], 'image' => $r['image'], 'link' => $r['link'] ?? '', 'alt' => $r['alt'] ?? '',
                    'posicao' => $r['posicao'] ?: 'topo', 'sort' => (int) $r['sort'], 'is_active' => (int) $r['is_active'],
                ]);
                $n['banners']++;
            }
        });

        // PostgreSQL não avança a sequência do id quando se insere id explícito
        if (DB::getDriverName() === 'pgsql') {
            foreach (['stores', 'products', 'banners'] as $t) {
                DB::statement("SELECT setval(pg_get_serial_sequence('{$t}', 'id'), COALESCE((SELECT MAX(id) FROM {$t}), 1))");
            }
        }

        $this->info(sprintf('Importado: %d lojas, %d produtos, %d imagens, %d banners.', $n['stores'], $n['products'], $n['media'], $n['banners']));
        $this->warn('As lojas precisam de senha nova: use "Esqueci minha senha" ou /admin > "Senha temporária".');

        return self::SUCCESS;
    }
}
