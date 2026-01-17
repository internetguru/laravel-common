<?php

namespace InternetGuru\LaravelCommon\Traits;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

trait Ulid32
{
    public function ulidLink(string $content = ''): string
    {
        $url = $this->ulidUrl();
        $title = $this->ulidForHumans();
        $content = sprintf($content ?: '%s', '<tt>' . $this->shortUlidForHumans() . '</tt>');

        return view('ig-common::components.ulid-link', compact('url', 'title', 'content'))->render();
    }

    public function ulidUrl($usp = null): string
    {
        $route = strtolower(class_basename($this)) . '.show';
        $ulid = $this->ulid;
        $usp = $usp ?? Route::currentRouteName();

        return route($route, compact('ulid', 'usp'));
    }

    public function shortUlidForHumans(): string
    {
        return strtoupper(substr($this->ulidForHumans(), -7));
    }

    public function ulidForHumans(): string
    {
        return strtoupper(substr($this->ulid, 0, 4)
            . '-' . substr($this->ulid, 4, 6)
            . '-' . substr($this->ulid, 10, 6)
            . '-' . substr($this->ulid, 16, 6)
            . '-' . substr($this->ulid, 22));
    }

    public static function generateBase32Uuid(): string
    {
        $uuid = Str::uuid();
        $binary = hex2bin(str_replace('-', '', $uuid));

        return strtolower(self::encodeCrockfordBase32($binary));
    }

    private static function encodeCrockfordBase32(string $binary): string
    {
        $alphabet = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $base32 = '';
        for ($i = 0; $i < strlen($bits); $i += 5) {
            $chunk = substr($bits, $i, 5);
            $val = bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT));
            $base32 .= $alphabet[$val];
        }

        return $base32;
    }
}
