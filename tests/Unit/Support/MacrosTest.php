<?php

namespace Tests\Unit\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Tests\TestCase;

class MacrosTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure macros are registered
        initNumberMacros();
        initCarbonMacros();
    }

    public function test_number_currency_for_humans_macro_exists()
    {
        // Test that the macro is registered
        $this->assertTrue(Number::hasMacro('currencyForHumans'));
        $this->assertTrue(Number::hasMacro('formatCurrencyToInput'));
    }

    public function test_carbon_date_for_humans()
    {
        $date = Carbon::parse('2024-01-15');
        $result = $date->dateForHumans();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_carbon_date_time_for_humans()
    {
        $date = Carbon::parse('2024-01-15 14:30:00');
        $result = $date->dateTimeForHumans();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_carbon_my_diff_for_humans_just_now()
    {
        $date = Carbon::now()->subSeconds(30);
        $result = $date->myDiffForHumans();

        $this->assertIsString($result);
    }

    public function test_carbon_my_diff_for_humans_minutes_ago()
    {
        $date = Carbon::now()->subMinutes(5);
        $result = $date->myDiffForHumans();

        $this->assertIsString($result);
        $this->assertStringNotContainsString('just_now', $result);
    }

    public function test_carbon_my_diff_for_humans_one_year()
    {
        // Test edge case: 11 months and 20 days
        $date = Carbon::now()->subMonths(11)->subDays(20);
        $result = $date->myDiffForHumans();

        $this->assertIsString($result);
    }

    public function test_carbon_time_for_humans()
    {
        $date = Carbon::parse('2024-01-15 14:30:00');
        $result = $date->timeForHumans();

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function test_carbon_random_work_time()
    {
        $date = Carbon::parse('2024-01-15 12:00:00');
        $result = $date->randomWorkTime(9, 17);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertGreaterThanOrEqual(9, $result->hour);
        $this->assertLessThan(17, $result->hour);
    }

    public function test_carbon_random_work_time_with_custom_range()
    {
        $date = Carbon::parse('2024-01-15 12:00:00');
        $result = $date->randomWorkTime(8, 18);

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertGreaterThanOrEqual(8, $result->hour);
        $this->assertLessThan(18, $result->hour);
    }

    public function test_carbon_to_display_timezone_uses_session()
    {
        Session::put('display_timezone', 'America/New_York');

        $date = Carbon::parse('2024-06-15 12:00:00', 'UTC');
        $result = $date->toDisplayTimezone();

        $this->assertEquals('America/New_York', $result->tzName);
    }

    public function test_carbon_to_display_timezone_falls_back_to_config()
    {
        Session::forget('display_timezone');
        config(['app.timezone' => 'Europe/Prague']);

        $date = Carbon::parse('2024-06-15 12:00:00', 'UTC');
        $result = $date->toDisplayTimezone();

        $this->assertEquals('Europe/Prague', $result->tzName);
    }

    public function test_str_ref_default_length()
    {
        $this->assertEquals(6, strlen(Str::ref()));
    }

    public function test_str_ref_custom_length()
    {
        $this->assertEquals(10, strlen(Str::ref(10)));
    }

    public function test_str_ref_starts_with_letter()
    {
        for ($i = 0; $i < 30; $i++) {
            $this->assertMatchesRegularExpression('/^[a-z]/', Str::ref());
        }
    }

    public function test_str_ref_contains_at_least_one_digit()
    {
        for ($i = 0; $i < 30; $i++) {
            $this->assertMatchesRegularExpression('/[2-9]/', Str::ref());
        }
    }

    public function test_str_ref_excludes_ambiguous_characters()
    {
        for ($i = 0; $i < 50; $i++) {
            $this->assertDoesNotMatchRegularExpression('/[ilo01u]/i', Str::ref(10));
        }
    }

    public function test_str_ref_throws_for_length_less_than_2()
    {
        $this->expectException(\InvalidArgumentException::class);
        Str::ref(1);
    }

    public function test_number_currency_for_humans_returns_symbol_without_number()
    {
        Number::useCurrency('USD');
        $symbol = Number::currencyForHumans();
        $this->assertIsString($symbol);
        $this->assertNotEmpty($symbol);
    }

    public function test_number_currency_for_humans_formats_number()
    {
        Number::useCurrency('USD');
        $result = Number::currencyForHumans(1000);
        $this->assertIsString($result);
        $this->assertStringContainsString('1', $result);
    }
}
