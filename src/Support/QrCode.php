<?php

namespace InternetGuru\LaravelCommon\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /** Longest content that still renders as a reasonably scannable QR code. */
    public const MAX_LENGTH = 512;

    /**
     * Render the given content as an inline SVG QR code.
     *
     * Content longer than self::MAX_LENGTH is truncated, as the encoder throws on oversized data.
     * The XML prolog is stripped so the result can be embedded directly in HTML.
     */
    public static function svg(string $content, int $size = 240, int $margin = 1): string
    {
        $content = mb_strcut($content, 0, self::MAX_LENGTH);

        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        ));

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $writer->writeString($content));
    }
}
