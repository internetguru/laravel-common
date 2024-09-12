<?php

namespace Tests\Unit\Casts;

use InternetGuru\LaravelCommon\Casts\CarbonIntervalCast;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class CarbonIntervalCastTest extends TestCase
{
    public function test_casts_human_readable_string_to_carbon_interval_on_get()
    {
        $model = new class extends Model
        {
            protected $casts = [
                'maintenance_period' => 'carbon-interval',
            ];
        };

        $cast = new CarbonIntervalCast;

        $intervalString = '1 week';
        $interval = $cast->get($model, 'maintenance_period', $intervalString, []);

        $this->assertInstanceOf(CarbonInterval::class, $interval);
        $this->assertEquals(7, $interval->totalDays); // 1 week equals 7 days
    }

    public function test_casts_carbon_interval_to_human_readable_string_on_set()
    {
        $model = new class extends Model
        {
            protected $casts = [
                'maintenance_period' => 'carbon-interval',
            ];
        };

        $cast = new CarbonIntervalCast;

        $interval = CarbonInterval::week(); // 1 week
        $humanReadable = $cast->set($model, 'maintenance_period', $interval, []);

        $this->assertEquals('1 week', $humanReadable);
    }

    public function test_returns_null_if_value_is_null_on_get()
    {
        $model = new class extends Model
        {
            protected $casts = [
                'maintenance_period' => 'carbon-interval',
            ];
        };

        $cast = new CarbonIntervalCast;

        $result = $cast->get($model, 'maintenance_period', null, []);
        $this->assertNull($result);
    }

    public function test_returns_null_if_value_is_null_on_set()
    {
        $model = new class extends Model
        {
            protected $casts = [
                'maintenance_period' => 'carbon-interval',
            ];
        };

        $cast = new CarbonIntervalCast;

        $result = $cast->set($model, 'maintenance_period', null, []);
        $this->assertNull($result);
    }
}
