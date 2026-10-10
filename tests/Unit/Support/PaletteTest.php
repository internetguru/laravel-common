<?php

namespace Tests\Unit\Support;

use InternetGuru\LaravelCommon\Support\Palette;
use InvalidArgumentException;
use Tests\TestCase;

class PaletteTest extends TestCase
{
    public function test_the_first_slots_are_the_furthest_apart()
    {
        $hues = array_map(fn (int $slot) => Palette::hue($slot), range(0, 7));

        $this->assertSame([0.0, 180.0, 90.0, 270.0, 45.0, 225.0, 135.0, 315.0], $hues);
    }

    public function test_a_slot_keeps_its_colour_however_large_the_set()
    {
        $this->assertSame(Palette::color(3), Palette::colors(['a', 'b', 'c', 'd'])['d']);
        $this->assertSame(Palette::color(3), Palette::colors(range('a', 'z'))['d']);
    }

    public function test_no_two_slots_share_a_colour()
    {
        $colors = array_map(fn (int $slot) => Palette::color($slot), range(0, 99));

        $this->assertCount(100, array_unique($colors));
    }

    public function test_slots_past_the_first_eight_are_drawn_darker()
    {
        $this->assertStringStartsWith('oklch(65% ', Palette::color(7));
        $this->assertStringStartsWith('oklch(55% ', Palette::color(8));
        $this->assertStringStartsWith('oklch(65% ', Palette::color(16));
    }

    public function test_a_seed_turns_every_colour_by_the_same_angle()
    {
        $this->assertSame(200.0, Palette::hue(0, 200));
        $this->assertSame(20.0, Palette::hue(1, 200));
        $this->assertSame(340.0, Palette::hue(0, -20));
        $this->assertSame(Palette::hue(2, 'payment-types'), Palette::hue(2, 'payment-types'));
        $this->assertNotSame(Palette::hue(0, 'payment-types'), Palette::hue(0, 'branches'));
    }

    public function test_every_key_takes_the_slot_of_its_position()
    {
        $this->assertSame(['a' => 0, 'b' => 1, 'c' => 2], Palette::slots(['a', 'b', 'c']));
    }

    public function test_a_forced_slot_moves_the_key_it_was_taken_from_along()
    {
        $this->assertSame(
            ['a' => 2, 'b' => 1, 'c' => 3, 'd' => 0],
            Palette::slots(['a', 'b', 'c', 'd'], ['a' => 2]),
        );
    }

    public function test_a_slot_forced_past_the_end_drops_the_one_left_over()
    {
        $this->assertSame(
            ['a' => 4, 'b' => 1, 'c' => 2, 'd' => 3],
            Palette::slots(['a', 'b', 'c', 'd'], ['a' => 4]),
        );
    }

    public function test_slots_forced_on_keys_outside_the_set_are_ignored()
    {
        $this->assertSame(['a' => 0, 'b' => 1], Palette::slots(['a', 'b'], ['z' => 9]));
    }

    public function test_a_negative_slot_is_refused()
    {
        $this->expectException(InvalidArgumentException::class);

        Palette::slots(['a'], ['a' => -1]);
    }

    public function test_variants_are_drawn_in_tones_of_the_palette()
    {
        $this->assertSame('oklch(65% 0.11 145)', Palette::variant('success'));
        $this->assertSame('oklch(65% 0 0)', Palette::variant('secondary'));
        $this->assertNull(Palette::variant('expression(alert(1))'));
    }
}
