<?php

namespace Tests\Unit\Support;

use InternetGuru\LaravelCommon\Rules\Ulid32;
use InternetGuru\LaravelCommon\Support\Sanitizer;
use InvalidArgumentException;
use Tests\TestCase;

class SanitizerTest extends TestCase
{
    private Sanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.debug' => false]);

        $this->sanitizer = new Sanitizer;
    }

    private function sanitize(array $data, array $rules = [], array $map = []): array
    {
        return $this->sanitizer->sanitize($data, $rules, $map);
    }

    public function test_base_trims_and_collapses_horizontal_whitespace()
    {
        $result = $this->sanitize(['note' => "  Hello   there\tworld  "]);

        $this->assertSame('Hello there world', $result['note']);
    }

    public function test_base_keeps_line_breaks_so_a_textarea_keeps_its_paragraphs()
    {
        $result = $this->sanitize(['note' => "First  line\r\n\r\nSecond   line"]);

        $this->assertSame("First line\n\nSecond line", $result['note']);
    }

    public function test_normalize_newlines_collapses_runs_of_blank_lines()
    {
        $result = $this->sanitize(['note' => "a\n\n\n\n\nb"]);

        $this->assertSame("a\n\nb", $result['note']);
    }

    public function test_squish_flattens_the_same_value_line_breaks_included()
    {
        $result = $this->sanitize(['name' => "First  line\n\nSecond   line"]);

        $this->assertSame('First line Second line', $result['name']);
    }

    public function test_strip_invisible_removes_zero_width_and_bidi_characters()
    {
        $result = $this->sanitize(['note' => "a\u{200B}b\u{202E}c\u{FEFF}d"]);

        $this->assertSame('abcd', $result['note']);
    }

    public function test_email_is_trimmed_ascii_folded_and_lowercased()
    {
        $result = $this->sanitize(['email' => '  Fóo@Example.COM  ']);

        $this->assertSame('foo@example.com', $result['email']);
    }

    public function test_email_drops_internal_whitespace()
    {
        $result = $this->sanitize(['email' => 'foo @ example.com']);

        $this->assertSame('foo@example.com', $result['email']);
    }

    public function test_operations_are_idempotent()
    {
        $once = $this->sanitize(['email' => '  Fóo@Example.COM  ', 'note' => "  a \r\n\r\n  b  "]);
        $twice = $this->sanitize($once);

        $this->assertSame($once, $twice);
    }

    public function test_except_beats_every_other_match()
    {
        $result = $this->sanitize(['password' => '  Sec ret  ', 'current_password' => ' x ']);

        $this->assertSame('  Sec ret  ', $result['password']);
        $this->assertSame(' x ', $result['current_password']);
    }

    public function test_except_accepts_a_glob_so_secrets_can_be_covered_as_a_family()
    {
        config(['ig-common.sanitize.except' => ['*_secret', '*_api_key']]);

        $result = $this->sanitize([
            'gateway_stripe_secret' => '  sk_live_ABC  ',
            'gateway' => ['besteron_api_key' => '  key  '],
        ]);

        $this->assertSame('  sk_live_ABC  ', $result['gateway_stripe_secret']);
        $this->assertSame('  key  ', $result['gateway']['besteron_api_key']);
    }

    public function test_except_covers_a_nested_occurrence_of_the_name()
    {
        $result = $this->sanitize(['user' => ['password' => '  Sec ret  ']]);

        $this->assertSame('  Sec ret  ', $result['user']['password']);
    }

    public function test_a_call_site_map_beats_a_field_name_match()
    {
        $result = $this->sanitize(['email' => ' Foo Bar '], [], ['email' => 'name']);

        $this->assertSame('Foo Bar', $result['email']);
    }

    public function test_a_field_name_match_beats_a_rule_match()
    {
        // 'note' is free text by name; the numeric rule would have stripped the space.
        $result = $this->sanitize(['note' => 'a b'], ['note' => 'numeric']);

        $this->assertSame('a b', $result['note']);
    }

    public function test_a_rule_match_types_a_field_the_name_did_not()
    {
        $result = $this->sanitize(['contact' => '  Foo@Example.COM '], ['contact' => 'required|email:rfc,dns']);

        $this->assertSame('foo@example.com', $result['contact']);
    }

    public function test_a_rule_object_and_a_rule_class_string_both_resolve()
    {
        $fromObject = $this->sanitize(['identifier' => ' AB-CD 12 '], ['identifier' => [new Ulid32]]);
        $fromClass = $this->sanitize(['identifier' => ' AB-CD 12 '], ['identifier' => [Ulid32::class]]);

        $this->assertSame('abcd12', $fromObject['identifier']);
        $this->assertSame('abcd12', $fromClass['identifier']);
    }

    public function test_field_names_match_by_snake_case_normalization()
    {
        $result = $this->sanitize([
            'toEmail' => ' A@B.COM ',
            'to_email' => ' C@D.COM ',
            'inventoryNumber' => ' X 1 ',
        ]);

        $this->assertSame('a@b.com', $result['toEmail']);
        $this->assertSame('c@d.com', $result['to_email']);
        $this->assertSame('X1', $result['inventoryNumber']);
    }

    public function test_a_type_declared_under_a_camel_case_name_matches_a_nested_key()
    {
        // 'contactWidget' does not snake-case to itself, so a key declared the
        // way a Livewire property is written has to match as written.
        config(['ig-common.sanitize.types.contactWidget' => 'email']);

        $result = $this->sanitize(['wrapper' => ['contactWidget' => ' A@B.COM ']]);

        $this->assertSame('a@b.com', $result['wrapper']['contactWidget']);
    }

    public function test_nested_keys_are_typed_by_their_last_named_segment()
    {
        $result = $this->sanitize(['items' => [['email' => ' A@B.COM ']]]);

        $this->assertSame('a@b.com', $result['items'][0]['email']);
    }

    public function test_numeric_segments_are_skipped_when_choosing_the_name()
    {
        $result = $this->sanitize(['to_email' => [' A@B.COM ', ' C@D.COM ']]);

        $this->assertSame(['a@b.com', 'c@d.com'], $result['to_email']);
    }

    public function test_a_key_containing_dots_is_one_segment_not_several()
    {
        // laravel-feedback keys its recipients by e-mail address and holds the
        // recipient's name. Splitting that key on dots would read the type from
        // 'com' and, on write-back, bury the name under invented keys.
        $result = $this->sanitize(['recipients' => ['lavinia.stamm@gmail.com' => '  Lavinia   Stamm ']]);

        $this->assertSame(['recipients' => ['lavinia.stamm@gmail.com' => 'Lavinia Stamm']], $result);
    }

    public function test_a_collection_types_the_values_its_own_keys_cannot()
    {
        $result = $this->sanitize(['recipients' => ['a.b@c.com' => ' A  B ', 'd@e.com' => ' D  E ']]);

        $this->assertSame(['a.b@c.com' => 'A B', 'd@e.com' => 'D E'], $result['recipients']);
    }

    public function test_a_deeper_name_wins_over_an_ancestor()
    {
        $result = $this->sanitize(['note' => ['email' => ' A@B.COM ']]);

        $this->assertSame('a@b.com', $result['note']['email']);
    }

    public function test_wildcard_rule_keys_expand_against_the_data()
    {
        $result = $this->sanitize(
            ['contacts' => [['who' => ' A@B.COM '], ['who' => ' C@D.COM ']]],
            ['contacts.*.who' => 'email']
        );

        $this->assertSame('a@b.com', $result['contacts'][0]['who']);
        $this->assertSame('c@d.com', $result['contacts'][1]['who']);
    }

    public function test_normalize_list_reformats_a_comma_separated_value()
    {
        $result = $this->sanitize(['allergens' => ' 3 ,1,,7 ']);

        $this->assertSame('3, 1, 7', $result['allergens']);
    }

    public function test_phone_drops_grouping_separators_but_not_letters()
    {
        $result = $this->sanitize(['phone' => ' +420 777 123 456 ', 'work_phone' => '(02) 1234-5678']);

        $this->assertSame('+420777123456', $result['phone']);
        $this->assertSame('0212345678', $result['work_phone']);

        // A value that was never a phone number stays invalid rather than
        // being scrubbed into something that passes.
        $this->assertSame('notaphone', $this->sanitize(['phone' => 'not a phone'])['phone']);
    }

    public function test_subdomain_reproduces_the_normalization_acquire_controller_does_by_hand()
    {
        $result = $this->sanitize(['subdomain' => ' Můj-Obchod ']);

        $this->assertSame('muj-obchod', $result['subdomain']);
    }

    public function test_non_string_values_pass_through_untouched()
    {
        $data = ['seats' => 4, 'anonymous' => true, 'vouchers' => null, 'meta' => []];

        $this->assertSame($data, $this->sanitize($data));
    }

    public function test_pipeline_names_expand_recursively()
    {
        config(['ig-common.sanitize.pipelines.shout' => ['name', 'upper']]);

        $result = $this->sanitize(['whatever' => '  a  b '], [], ['whatever' => 'shout']);

        $this->assertSame('A B', $result['whatever']);
    }

    public function test_a_pipeline_cycle_throws_naming_the_cycle()
    {
        config([
            'ig-common.sanitize.pipelines.ping' => ['pong'],
            'ig-common.sanitize.pipelines.pong' => ['ping'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('references itself');

        $this->sanitize(['whatever' => 'x'], [], ['whatever' => 'ping']);
    }

    public function test_an_undefined_pipeline_name_throws()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('is not defined');

        $this->sanitize(['whatever' => 'x'], [], ['whatever' => 'nope']);
    }

    public function test_a_custom_invokable_class_runs_as_an_operation()
    {
        config(['ig-common.sanitize.pipelines.reversed' => ['base', ReverseOperation::class]]);

        $result = $this->sanitize(['whatever' => ' abc '], [], ['whatever' => 'reversed']);

        $this->assertSame('cba', $result['whatever']);
    }

    public function test_nothing_is_changed_when_the_feature_is_disabled()
    {
        config(['ig-common.sanitize.enabled' => false]);

        $data = ['email' => '  Fóo@Example.COM  '];

        $this->assertSame($data, $this->sanitize($data));
    }
}

class ReverseOperation
{
    public function __invoke(string $value): string
    {
        return strrev($value);
    }
}
