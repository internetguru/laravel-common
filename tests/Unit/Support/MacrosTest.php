<?php

namespace Tests\Unit\Support;

use Carbon\Carbon;
use Illuminate\Support\Number;
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
}
