<?php

namespace Database\Seeders\Support;

/**
 * Generates attractive gradient placeholder images with GD so seeding works
 * fully offline (no external image services).
 */
final class PlaceholderImage
{
    /** @var list<array{0: string, 1: string}> */
    private const PALETTES = [
        ['#6366F1', '#A855F7'], ['#0EA5E9', '#22D3EE'], ['#F43F5E', '#FB923C'],
        ['#10B981', '#84CC16'], ['#F59E0B', '#EF4444'], ['#8B5CF6', '#EC4899'],
        ['#14B8A6', '#3B82F6'], ['#64748B', '#0F172A'],
    ];

    public static function make(string $label, int $seed, int $size = 800): string
    {
        [$from, $to] = self::PALETTES[$seed % count(self::PALETTES)];
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, true);

        [$r1, $g1, $b1] = sscanf($from, '#%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf($to, '#%02x%02x%02x');

        // Diagonal gradient.
        for ($y = 0; $y < $size; $y++) {
            $t = $y / $size;
            $color = imagecolorallocate($img,
                (int) ($r1 + ($r2 - $r1) * $t),
                (int) ($g1 + ($g2 - $g1) * $t),
                (int) ($b1 + ($b2 - $b1) * $t));
            imageline($img, 0, $y, $size, $y, $color);
        }

        // Soft decorative circles.
        $white = imagecolorallocatealpha($img, 255, 255, 255, 105);
        imagefilledellipse($img, (int) ($size * 0.82), (int) ($size * 0.18), (int) ($size * 0.7), (int) ($size * 0.7), $white);
        imagefilledellipse($img, (int) ($size * 0.12), (int) ($size * 0.9), (int) ($size * 0.5), (int) ($size * 0.5), $white);

        // Initials, rendered with the built-in font and scaled up.
        $initials = collect(preg_split('/\s+/', trim($label)))
            ->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        $font = 5;
        $tw = imagefontwidth($font) * strlen($initials);
        $th = imagefontheight($font);
        $text = imagecreatetruecolor($tw, $th);
        imagesavealpha($text, true);
        imagefill($text, 0, 0, imagecolorallocatealpha($text, 0, 0, 0, 127));
        imagestring($text, $font, 0, 0, $initials, imagecolorallocate($text, 255, 255, 255));

        $scale = (int) ($size * 0.32 / $th);
        imagecopyresampled($img, $text,
            (int) (($size - $tw * $scale) / 2), (int) (($size - $th * $scale) / 2), 0, 0,
            $tw * $scale, $th * $scale, $tw, $th);

        $path = tempnam(sys_get_temp_dir(), 'seed').'.png';
        imagepng($img, $path, 6);

        return $path;
    }
}
