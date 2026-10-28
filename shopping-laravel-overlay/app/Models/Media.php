<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/** Imagem guardada no banco (WebP em base64). `$media->data` devolve/aceita os bytes binários. */
class Media extends Model
{
    protected $table = 'media';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function data(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => $v === null ? null : base64_decode($v),
            set: fn ($v) => $v === null ? null : base64_encode($v),
        );
    }
}
