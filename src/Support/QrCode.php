<?php

namespace InternetGuru\LaravelCommon\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCode
{
    /**
     * Render the given content as an inline SVG QR code.
     *
     * The XML prolog is stripped so the result can be embedded directly in HTML.
     */
    public static function svg(string $content, int $size = 240, int $margin = 1): string
    {
        $writer = new Writer(new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        ));

        return preg_replace('/^<\?xml.*?\?>\s*/', '', $writer->writeString($content));
    }
}
