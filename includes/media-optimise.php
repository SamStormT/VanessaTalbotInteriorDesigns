<?php
/**
 * Image optimisation with GD.
 *
 * Every upload produces:
 *   originals/…/name-abc123.jpg   the untouched upload, private (blocked by .htaccess)
 *   media/…/name-abc123.jpg       main image, max 1920px wide (hero use; the path saved in content)
 *   media/…/name-abc123-web.jpg   content image, max 1200px wide
 *   media/…/name-abc123-thumb.jpg thumbnail, max 400px on its longest side
 *
 * Images are never enlarged. Re-encoding removes embedded metadata such as the
 * GPS location of a photo. If a smaller file can't be made, the source is used.
 */

declare(strict_types=1);

const MEDIA_MAIN_WIDTH  = 1920;
const MEDIA_WEB_WIDTH   = 1200;
const MEDIA_THUMB_SIZE  = 400;
const MEDIA_MAX_PIXELS  = 50000000; // about 50 megapixels
const MEDIA_JPEG_QUALITY = 82;
const MEDIA_WEBP_QUALITY = 80;

/** Path of a size variant: "a/b.jpg" + "web" gives "a/b-web.jpg". */
function media_variant_path(string $path, string $size): string
{
    return (string) preg_replace('/(\.[A-Za-z0-9]+)$/', '-' . $size . '$1', $path);
}

/** The private copy of an original, mirroring the public path. */
function media_original_path(string $path): string
{
    return (string) preg_replace('#^uploads/media/#', 'uploads/originals/', $path);
}

/** True for animated GIF and WebP files, which GD would flatten to one frame. */
function media_is_animated(string $file, string $mime): bool
{
    $data = (string) file_get_contents($file);
    if ($mime === 'image/gif') {
        // Each frame has a graphic control block followed by an image block.
        return preg_match_all('/\x21\xF9\x04.{4}\x00\x2C/s', $data) > 1;
    }
    if ($mime === 'image/webp') {
        return str_contains(substr($data, 0, 64), 'ANIM') || str_contains($data, 'ANMF');
    }
    return false;
}

function media_load(string $file, string $mime): GdImage
{
    $image = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($file),
        'image/png'  => @imagecreatefrompng($file),
        'image/webp' => @imagecreatefromwebp($file),
        'image/gif'  => @imagecreatefromgif($file),
        default      => false,
    };
    if (!$image instanceof GdImage) {
        throw new RuntimeException('The image data could not be read.');
    }
    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }
    return $image;
}

/** Turn a phone photo the right way up, using the orientation it was saved with. */
function media_apply_orientation(GdImage $image, string $file, string $mime): GdImage
{
    if ($mime !== 'image/jpeg' || !function_exists('exif_read_data')) {
        return $image;
    }
    $exif  = @exif_read_data($file);
    $angle = match ((int) ($exif['Orientation'] ?? 1)) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
    if ($angle === 0) {
        return $image;
    }
    $rotated = imagerotate($image, $angle, 0);
    return $rotated instanceof GdImage ? $rotated : $image;
}

/** A resized copy, keeping transparency. Never enlarges. */
function media_resize(GdImage $image, int $maxWidth, ?int $maxHeight = null): GdImage
{
    $width  = imagesx($image);
    $height = imagesy($image);
    $scale  = min(1, $maxWidth / $width, $maxHeight === null ? 1 : $maxHeight / $height);

    if ($scale >= 1) {
        return $image;
    }

    $newWidth  = max(1, (int) round($width * $scale));
    $newHeight = max(1, (int) round($height * $scale));
    $resized   = imagecreatetruecolor($newWidth, $newHeight);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    return $resized;
}

function media_save(GdImage $image, string $mime, string $file): void
{
    imagesavealpha($image, true);
    $saved = match ($mime) {
        'image/jpeg' => (static function () use ($image, $file): bool {
            imageinterlace($image, true); // progressive: shows sooner on slow connections
            return imagejpeg($image, $file, MEDIA_JPEG_QUALITY);
        })(),
        'image/png'  => imagepng($image, $file, 9),
        'image/webp' => imagewebp($image, $file, MEDIA_WEBP_QUALITY),
        'image/gif'  => (static function () use ($image, $file): bool {
            $copy = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagecopy($copy, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagetruecolortopalette($copy, true, 256);
            return imagegif($copy, $file);
        })(),
        default      => false,
    };
    if (!$saved || !is_file($file)) {
        throw new RuntimeException('The optimised image could not be written.');
    }
}

/**
 * Write the main, web and thumbnail versions of $source.
 * Returns ['width', 'height', 'size', 'status'] for the main version.
 * Throws RuntimeException if the image can't be processed; callers fall back.
 */
function media_optimise(string $source, string $mime, string $mainFile): array
{
    $info = @getimagesize($source);
    if ($info === false) {
        throw new RuntimeException('The image could not be read.');
    }
    [$width, $height] = $info;

    $targets = [
        $mainFile                                => [MEDIA_MAIN_WIDTH, null],
        media_variant_path($mainFile, 'web')     => [MEDIA_WEB_WIDTH, null],
        media_variant_path($mainFile, 'thumb')   => [MEDIA_THUMB_SIZE, MEDIA_THUMB_SIZE],
    ];

    // Animations would lose every frame but the first, so they are used as they are.
    if (media_is_animated($source, $mime)) {
        foreach (array_keys($targets) as $target) {
            media_copy($source, $target);
        }
        return ['width' => $width, 'height' => $height, 'size' => (int) filesize($mainFile), 'status' => 'Unchanged'];
    }

    if ($width * $height > MEDIA_MAX_PIXELS) {
        throw new RuntimeException('The image is too large to optimise.');
    }
    if ((int) ini_get('memory_limit') > 0 && (int) ini_get('memory_limit') < 512) {
        @ini_set('memory_limit', '512M');
    }

    $image   = media_load($source, $mime);
    $rotated = media_apply_orientation($image, $source, $mime);
    $turned  = $rotated !== $image;
    $image   = $rotated;

    foreach ($targets as $target => [$maxWidth, $maxHeight]) {
        $version   = media_resize($image, $maxWidth, $maxHeight);
        $unchanged = $version === $image && !$turned;
        $temporary = $target . '.tmp';

        media_save($version, $mime, $temporary);

        // Re-saving an already small, optimised file can make it bigger; keep the smaller one.
        if ($unchanged && filesize($temporary) >= filesize($source)) {
            @unlink($temporary);
            media_copy($source, $target);
        } elseif (!rename($temporary, $target)) {
            @unlink($temporary);
            throw new RuntimeException('The optimised image could not be saved.');
        }
    }

    $main = @getimagesize($mainFile);
    return [
        'width'  => (int) ($main[0] ?? $width),
        'height' => (int) ($main[1] ?? $height),
        'size'   => (int) filesize($mainFile),
        'status' => 'Optimised',
    ];
}

function media_copy(string $from, string $to): void
{
    if ($from !== $to && !copy($from, $to)) {
        throw new RuntimeException('The image could not be copied.');
    }
}

/** Create a folder for a file if needed. */
function media_make_folder(string $file): void
{
    $folder = dirname($file);
    if (!is_dir($folder) && !mkdir($folder, 0755, true) && !is_dir($folder)) {
        error_log('Could not create upload folder: ' . $folder);
        throw new RuntimeException('That file could not be saved. Please try again.');
    }
}
