<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $t) {
            $t->id();
            $t->string('email', 160)->unique();              // login + recuperação de senha
            $t->string('password', 256);
            $t->string('name', 120);
            $t->string('slug', 140)->unique();
            $t->string('instagram', 30);
            $t->string('phone', 11);                         // telefone comercial (só dígitos)
            $t->string('category', 60)->default('Outros');
            $t->text('description')->nullable();
            $t->string('whatsapp', 30)->default('');
            $t->string('address', 200)->default('');
            $t->string('logo', 32)->nullable();              // id em `media`
            $t->string('open_time', 5)->default('09:00');
            $t->string('close_time', 5)->default('18:00');
            $t->string('open_days', 20)->default('0,1,2,3,4,5');
            $t->boolean('is_active')->default(true);
            $t->boolean('must_change_pw')->default(false);   // senha temporária: obriga trocar
            $t->timestamp('consent_at')->nullable();         // aceite LGPD
            $t->timestamps();
        });

        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('store_id')->constrained()->cascadeOnDelete();
            $t->string('name', 120);
            $t->string('price', 30)->default('');
            $t->string('description', 300)->default('');
            $t->string('image', 32)->nullable();             // id em `media`
        });

        // Imagens ficam no banco (base64), como no projeto original: sem disco, sobrevivem a deploys.
        Schema::create('media', function (Blueprint $t) {
            $t->string('id', 32)->primary();
            $t->longText('data');
        });

        Schema::create('banners', function (Blueprint $t) {
            $t->id();
            $t->string('image', 32);
            $t->string('link', 300)->default('');
            $t->string('alt', 120)->default('');
            $t->string('posicao', 10)->default('topo');      // "topo" (carrossel) ou "faixa" (tira larga)
            $t->integer('sort')->default(0);
            $t->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
        Schema::dropIfExists('media');
        Schema::dropIfExists('products');
        Schema::dropIfExists('stores');
    }
};
