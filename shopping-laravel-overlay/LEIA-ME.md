# Shopping Virtual Ponta Grossa — versão Laravel

Conversão do projeto Flask para Laravel (11 ou superior). Esta pasta **não é um Laravel completo**:
são só os arquivos do seu app, para serem copiados por cima de uma instalação nova (assim você pega
sempre a versão atual do framework com o `composer`).

> ⚠️ Esta conversão foi escrita sem poder executar PHP/Composer no ambiente em que foi feita.
> Revisei o código e rodei uma checagem estática (diretivas Blade e balanceamento de chaves),
> mas **ainda não foi executada**. Rode o passo a passo abaixo e, se aparecer qualquer erro, me mande a mensagem.

## Requisitos
- PHP 8.2+ com as extensões `gd` (**com suporte a WebP**), `mbstring`, `fileinfo`, `pdo_sqlite` ou `pdo_mysql`/`pdo_pgsql`; `exif` é opcional (corrige a rotação de fotos de celular)
- Composer
- No `php.ini`: `upload_max_filesize = 6M`, `post_max_size = 8M`, `memory_limit = 256M`

## Instalação (Windows / PowerShell)
```powershell
composer create-project laravel/laravel shopping-laravel

# copia os arquivos desta pasta por cima (sobrescreve bootstrap/app.php e app/Providers/AppServiceProvider.php)
Copy-Item -Recurse -Force .\shopping-laravel-overlay\* .\shopping-laravel\

cd shopping-laravel
```
1. Abra o `.env` e acrescente/ajuste as linhas de **`.env.additions`** (principalmente `AUTH_MODEL` e `ADMIN_EMAIL`).
   Se preferir, em vez do `AUTH_MODEL` edite `config/auth.php` e troque `User::class` por `\App\Models\Store::class`.
2. `php artisan migrate`  (o Laravel 11+ usa SQLite por padrão, igual ao seu modo local)
3. `php artisan serve` → http://127.0.0.1:8000

Opcional: apague `app/Models/User.php` e `database/factories/UserFactory.php` (a loja substitui o usuário).
Não apague a migration padrão `0001_01_01_...`: ela cria a tabela de sessões.

### Trazer os dados do Flask
```powershell
php artisan shopping:import-flask ..\shopping-pg\instance\shopping.db
```
Importa lojas, produtos, banners e imagens. **As senhas não migram** (veja "Pontos de atenção").
Para os dados de produção no Turso, baixe uma cópia do banco como arquivo SQLite e passe o caminho dela
(o Turso CLI tem um comando de export para isso; confira na documentação dele).

## Mapa Flask → Laravel
| Flask | Laravel |
|---|---|
| `app.py` (rotas) | `routes/web.php` + `app/Http/Controllers/*` |
| `Store`, `Product`, `Media`, `Banner` | `app/Models/*` + `database/migrations/…create_shopping_tables.php` |
| Flask-Login | Guard `web` com `Store` como usuário · `StoreSession` (loja inativa / senha temporária) |
| Flask-WTF (CSRF) | CSRF nativo do Laravel (`@csrf`) |
| Flask-Limiter | `RateLimiter` em `AppServiceProvider` + `throttle:` nas rotas POST |
| Pillow (`save_image`) | `app/Support/ImageStore.php` (GD: valida, tira EXIF, redimensiona, WebP) |
| `after_request` (headers/CSP) | `app/Http/Middleware/SecurityHeaders.php` |
| `ProxyFix` | `trustProxies` em `bootstrap/app.php` |
| itsdangerous (link de senha) | URL assinada (`temporarySignedRoute`), expira em 1h, uso único |
| Brevo/SMTP | Mail do Laravel (`MAIL_*` no `.env`) |
| Jinja `templates/` | Blade `resources/views/` (filtros `fone`/`wa` viraram `phone_fmt` / `waLink()`) |
| `static/` | `public/assets/` |
| `/saude` | `/saude` (fora do grupo `web`: não abre sessão nem toca no banco) |
| `render.yaml` | não se aplica (veja "Hospedagem") |

As URLs e os nomes de rota foram mantidos (`/loja/<slug>`, `/painel`, `/admin`, `/midia/<id>`…).

## Pontos de atenção
- **Senhas:** o Flask usava scrypt e o PHP não verifica esse formato. Lojas importadas ficam com senha aleatória
  e `must_change_pw`; cada lojista usa "Esqueci minha senha" (se o e-mail estiver configurado) ou você gera uma
  "Senha temporária" em `/admin`. Lojas criadas já no Laravel usam bcrypt normalmente.
- **Banco:** o Laravel não tem driver oficial para Turso/libSQL. Use SQLite (um servidor só), MySQL ou PostgreSQL.
  As imagens continuam **dentro do banco** (tabela `media`, base64), como no original.
  Se o site crescer, vale mover as imagens para `storage/` (ou S3) e servi-las pelo servidor web.
- **Hospedagem:** o Render não tem runtime PHP nativo (exigiria Docker). Hospedagem com PHP/MySQL, um VPS
  ou Laravel Forge/Herd funcionam sem adaptação. Aponte o monitor de uptime para `/saude`.
- **Rate limit:** o cache padrão do Laravel (`database`) guarda os contadores, então já funciona com vários processos
  (no Flask era em memória e exigia `--workers 1`).
- **Em produção** rode `php artisan config:cache route:cache view:cache` e defina `APP_DEBUG=false`.
  (Com `APP_DEBUG=true` a CSP é desligada de propósito, senão a página de erro do Laravel não renderiza.)

## Pequenas diferenças em relação ao original
- Link "Pedir pelo WhatsApp": o original não adicionava o `55` em números com DDD 55 (Rio Grande do Sul); corrigido.
- Loja aberta só um dia da semana mostrava "Qua a Qua"; agora mostra "Qua".
- Link de redefinição de senha: em vez do final do hash da senha, usa um HMAC dele (não expõe parte do hash).
- Banner com link inválido não deixa mais a imagem órfã no banco.
- Imagens: limite de 24 MP (o original aceitava 40 MP) por causa do consumo de memória do GD.
- Ao deslogar, a sessão inteira é invalidada.
