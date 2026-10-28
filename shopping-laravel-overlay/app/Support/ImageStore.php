<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;

/**
 * Equivalente ao save_image() do Flask: valida de verdade o arquivo, remove EXIF/metadados
 * (ao reencodar), redimensiona e grava como WebP na tabela `media`. Usa a extensão GD (com WebP).
 */
class ImageStore
{
    // O original aceitava 40 MP (Pillow). O GD guarda ~4 bytes/pixel na memória, então limitamos mais.
    private const MAX_PIXELS = 24_000_000;

    /** @return string|null id da mídia, ou null se não houver arquivo / for inválido */
    public static function save(?UploadedFile $file, int $maxSide = 1000): ?string
    {
        if (! $file || ! $file->isValid() || ! function_exists('imagewebp')) {
            return null;
        }

        $path = $file->getRealPath();
        $info = @getimagesize($path);
        if (! $info || $info[0] < 1 || $info[1] < 1
            || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $src = @imagecreatefromstring((string) file_get_contents($path));
        if (! $src) {
            return null;
        }
        if (! imageistruecolor($src)) {
            imagepalettetotruecolor($src);
        }

        // Respeita a orientação EXIF das fotos de celular (JPEG)
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $rot = imagerotate($src, $angle, 0);
                if ($rot) {
                    $src = $rot;
                }
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, $maxSide / max($w, $h));          // nunca amplia (como o thumbnail do Pillow)
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagewebp($dst, null, 80);
        $bin = (string) ob_get_clean();
        if ($bin === '') {
            return null;
        }

        $id = bin2hex(random_bytes(16));
        Media::create(['id' => $id, 'data' => $bin]);

        return $id;
    }
}
