<?php

namespace Tests\Unit\Support;

use Illuminate\Support\Collection;
use InternetGuru\LaravelCommon\Support\LabelGroups;
use InternetGuru\LaravelCommon\Support\Palette;
use Tests\TestCase;

class LabelGroupsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        LabelGroups::flush();
    }

    public function test_every_key_of_a_group_differs_in_color()
    {
        LabelGroups::register('locations', ['prg', 'brn', 'ost', 'plz', 'olo', 'lbc', 'pce', 'zln', 'jih']);

        $colors = LabelGroups::colors('locations');

        $this->assertSame(['prg', 'brn', 'ost', 'plz', 'olo', 'lbc', 'pce', 'zln', 'jih'], array_keys($colors));
        $this->assertCount(9, array_unique($colors));
    }

    public function test_the_group_name_turns_the_palette_unless_a_seed_is_given()
    {
        LabelGroups::register('locations', ['prg', 'brn']);
        LabelGroups::register('roles', ['admin', 'user'], seed: 40);

        $this->assertSame(Palette::colors(['prg', 'brn'], seed: 'locations'), LabelGroups::colors('locations'));
        $this->assertSame(Palette::colors(['admin', 'user'], seed: 40), LabelGroups::colors('roles'));
    }

    public function test_a_forced_slot_moves_a_key_to_another_color()
    {
        LabelGroups::register('locations', ['prg', 'brn', 'ost'], forced: ['ost' => 5]);

        $this->assertSame(Palette::color(5, 'locations'), LabelGroups::colors('locations')['ost']);
    }

    public function test_a_key_added_at_the_end_leaves_the_others_their_colors()
    {
        LabelGroups::register('locations', ['prg', 'brn']);
        $before = LabelGroups::colors('locations');

        LabelGroups::register('locations', ['prg', 'brn', 'ost']);
        $after = LabelGroups::colors('locations');

        $this->assertSame($before['prg'], $after['prg']);
        $this->assertSame($before['brn'], $after['brn']);
    }

    public function test_keys_from_a_closure_are_read_once_per_request()
    {
        $reads = 0;
        LabelGroups::register('locations', function () use (&$reads) {
            $reads++;

            return new Collection(['prg', 'brn']);
        });

        LabelGroups::colors('locations');
        LabelGroups::colors('locations');
        $this->assertSame(1, $reads);

        $this->app->forgetScopedInstances();
        LabelGroups::colors('locations');
        $this->assertSame(2, $reads);
    }

    public function test_registering_a_group_again_replaces_it()
    {
        LabelGroups::register('locations', ['prg', 'brn']);
        LabelGroups::colors('locations');

        LabelGroups::register('locations', ['ost']);

        $this->assertSame(['ost'], array_keys(LabelGroups::colors('locations')));
    }

    public function test_an_unknown_group_has_no_colors()
    {
        $this->assertNull(LabelGroups::colors('cities'));
        $this->assertNull(LabelGroups::colors(Collection::class));
    }
}
