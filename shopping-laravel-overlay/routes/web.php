<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// (/saude e /midia/{name} ficam em bootstrap/app.php, fora do grupo "web")

// ---- público
Route::get('/', [PublicController::class, 'index'])->name('index');
Route::get('/loja/{slug}', [PublicController::class, 'loja'])->name('loja');

// ---- contas
Route::get('/cadastro', [AccountController::class, 'cadastroForm'])->name('cadastro');
Route::post('/cadastro', [AccountController::class, 'cadastro'])->middleware('throttle:cadastro');

Route::get('/entrar', [AccountController::class, 'entrarForm'])->name('entrar');
Route::post('/entrar', [AccountController::class, 'entrar'])->middleware('throttle:entrar');

Route::post('/sair', [AccountController::class, 'sair'])->name('sair');

Route::get('/recuperar', [AccountController::class, 'recuperarForm'])->name('recuperar');
Route::post('/recuperar', [AccountController::class, 'recuperar'])->middleware('throttle:recuperar');

Route::get('/redefinir/{id}', [AccountController::class, 'redefinirForm'])->whereNumber('id')->name('redefinir');
Route::post('/redefinir/{id}', [AccountController::class, 'redefinir'])->whereNumber('id')->middleware('throttle:redefinir');

Route::middleware('auth')->group(function () {
    Route::get('/trocar-senha', [AccountController::class, 'trocarSenhaForm'])->name('trocar_senha');
    Route::post('/trocar-senha', [AccountController::class, 'trocarSenha']);

    // ---- painel do lojista
    Route::get('/painel', [PainelController::class, 'index'])->name('painel');
    Route::post('/painel', [PainelController::class, 'update']);
    Route::post('/painel/produto', [PainelController::class, 'addProduto'])->name('add_produto');
    Route::post('/painel/produto/{pid}/excluir', [PainelController::class, 'delProduto'])->whereNumber('pid')->name('del_produto');

    // ---- admin
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin');
        Route::post('/banner', [AdminController::class, 'bannerAdd'])->name('admin_banner_add');
        Route::post('/banner/{bid}/{acao}', [AdminController::class, 'banner'])
            ->whereNumber('bid')->where('acao', 'excluir|alternar')->name('admin_banner');
        Route::post('/{sid}/alternar', [AdminController::class, 'toggle'])->whereNumber('sid')->name('admin_toggle');
        Route::post('/{sid}/senha-temporaria', [AdminController::class, 'tempPw'])->whereNumber('sid')->name('admin_temp_pw');
    });
});
