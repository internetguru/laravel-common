<?php

namespace InternetGuru\LaravelCommon\Support;

use InvalidArgumentException;

/**
 * The colours labels are drawn in: one mild tone, every colour at least 3:1
 * against white, told apart by hue alone.
 *
 * A colour is picked by its slot. Slot k always has the same hue, whatever the
 * size of the set it belongs to: the hues follow the van der Corput sequence,
 * so each slot falls in the middle of the widest gap the earlier ones leave and
 * the first slots of a set are the furthest apart. Past the first eight hues,
 * further slots alternate with a darker ring of the same, so neighbouring hues
 * also differ in lightness. A seed turns the whole wheel, so different sets
 * start from different colours.
 */
class Palette
{
    /** How many slots the first ring holds before the darker one starts. */
    protected const RING = 8;

    /**
     * Lightness and chroma of each ring: the most chroma every hue still has
     * room for at that lightness in sRGB.
     *
     * @var array<int, array{int, float}>
     */
    protected const RINGS = [[65, 0.11], [55, 0.09]];

    /**
     * Theme variants as tones of the palette, each keeping the meaning of its
     * hue: green for success, red for danger, and so on.
     *
     * @var array<string, string>
     */
    protected const VARIANTS = [
        'primary' => 'oklch(65% 0.11 260)',
        'secondary' => 'oklch(65% 0 0)',
        'success' => 'oklch(65% 0.11 145)',
        'danger' => 'oklch(65% 0.11 25)',
        'warning' => 'oklch(65% 0.11 75)',
        'info' => 'oklch(65% 0.11 215)',
        'light' => 'oklch(85% 0 0)',
        'dark' => 'oklch(40% 0 0)',
    ];

    /**
     * The colour of a slot. A string seed is hashed into the angle the wheel
     * is turned by; an integer seed is that angle in degrees.
     */
    public static function color(int $slot, int|string|null $seed = null): string
    {
        [$lightness, $chroma] = self::RINGS[self::ring($slot)];

        return "oklch({$lightness}% {$chroma} " . self::hue($slot, $seed) . ')';
    }

    /** The hue of a slot in degrees, turned by the seed. */
    public static function hue(int $slot, int|string|null $seed = null): float
    {
        if ($slot < 0) {
            throw new InvalidArgumentException("A palette slot cannot be negative, $slot given.");
        }

        $fraction = 0.0;
        $weight = 0.5;

        for ($rest = $slot; $rest > 0; $rest >>= 1) {
            $fraction += ($rest & 1) * $weight;
            $weight /= 2;
        }

        return round(fmod($fraction * 360 + self::offset($seed), 360), 1);
    }

    /** The tone a theme variant is drawn in, or null for a name that is none. */
    public static function variant(string $variant): ?string
    {
        return self::VARIANTS[$variant] ?? null;
    }

    /**
     * The colour of every key of a set, as far apart as the set allows.
     *
     * @param  array<int, int|string>  $keys  The set in a stable order.
     * @param  array<int|string, int>  $forced  Slots given to some keys by hand.
     * @return array<int|string, string>
     */
    public static function colors(array $keys, array $forced = [], int|string|null $seed = null): array
    {
        return array_map(fn (int $slot) => self::color($slot, $seed), self::slots($keys, $forced));
    }

    /**
     * The slot of every key of a set. A key takes the slot forced on it, or the
     * one at its own position; when that is taken, the next free one, wrapping
     * round below the size of the set. Forcing a slot past the end widens the
     * set, so the slot left over drops out: forcing the key in a disliked
     * colour to a new slot gets rid of that colour.
     *
     * @param  array<int, int|string>  $keys  The set in a stable order.
     * @param  array<int|string, int>  $forced  Slots given to some keys by hand.
     * @return array<int|string, int>
     */
    public static function slots(array $keys, array $forced = []): array
    {
        $keys = array_values($keys);
        $forced = array_intersect_key($forced, array_flip($keys));

        foreach ($forced as $slot) {
            if ($slot < 0) {
                throw new InvalidArgumentException("A palette slot cannot be negative, $slot given.");
            }
        }

        $size = max(count($keys), $forced === [] ? 0 : max($forced) + 1);
        $taken = array_fill_keys($forced, true);
        $slots = [];

        foreach ($keys as $position => $key) {
            if (isset($forced[$key])) {
                $slots[$key] = $forced[$key];

                continue;
            }

            $slot = $position;

            while (isset($taken[$slot])) {
                $slot = ($slot + 1) % $size;
            }

            $taken[$slot] = true;
            $slots[$key] = $slot;
        }

        return $slots;
    }

    /** The angle in degrees a seed turns the wheel by. */
    public static function offset(int|string|null $seed): int
    {
        return match (true) {
            $seed === null => 0,
            is_int($seed) => (($seed % 360) + 360) % 360,
            default => crc32($seed) % 360,
        };
    }

    /**
     * Which ring a slot is drawn in: the first eight in the light one, then
     * each further run of slots, as long as all the slots before it, in turn
     * in the darker and the light one.
     */
    protected static function ring(int $slot): int
    {
        if ($slot < self::RING) {
            return 0;
        }

        $run = strlen(decbin($slot)) - strlen(decbin(self::RING));

        return ($run + 1) % 2;
    }
}
