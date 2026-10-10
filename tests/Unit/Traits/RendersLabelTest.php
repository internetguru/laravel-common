<?php

namespace Tests\Unit\Traits;

use InternetGuru\LaravelCommon\Contracts\HasLabel;
use InternetGuru\LaravelCommon\Support\Palette;
use InternetGuru\LaravelCommon\Traits\RendersLabel;
use Tests\TestCase;

enum Channel: string implements HasLabel
{
    use RendersLabel;

    case WEB = 'web';
    case SHOP = 'shop';
    case PHONE = 'phone';
    case MAIL = 'mail';

    public function label(): string
    {
        return $this->name;
    }

    public function variant(): ?string
    {
        return $this === self::MAIL ? 'danger' : null;
    }
}

enum Tier: string implements HasLabel
{
    use RendersLabel;

    case GOLD = 'gold';
    case SILVER = 'silver';
    case BRONZE = 'bronze';

    public function label(): string
    {
        return $this->name;
    }

    public function variant(): ?string
    {
        return null;
    }

    protected function labelColor(): string|int|null
    {
        return match ($this) {
            self::GOLD => 4,
            self::BRONZE => '#cd7f32',
            default => null,
        };
    }

    protected static function labelPaletteSeed(): int|string
    {
        return 0;
    }
}

class RendersLabelTest extends TestCase
{
    public function test_cases_without_a_variant_share_one_palette_in_colours_of_their_own()
    {
        $colors = Palette::colors(['WEB', 'SHOP', 'PHONE'], seed: Channel::class);

        $this->assertStringContainsString($colors['WEB'], Channel::WEB->toLabelHtml());
        $this->assertStringContainsString($colors['SHOP'], Channel::SHOP->toLabelHtml());
        $this->assertStringContainsString($colors['PHONE'], Channel::PHONE->toLabelHtml());
    }

    public function test_a_case_with_a_variant_keeps_its_tone()
    {
        $this->assertStringContainsString(Palette::variant('danger'), Channel::MAIL->toLabelHtml());
    }

    public function test_a_case_can_force_a_slot_or_a_colour_of_its_own()
    {
        $this->assertStringContainsString(Palette::color(4, 0), Tier::GOLD->toLabelHtml());
        $this->assertStringContainsString(Palette::color(1, 0), Tier::SILVER->toLabelHtml());
        $this->assertStringContainsString('--ig-label-dot: #cd7f32', Tier::BRONZE->toLabelHtml());
    }
}
