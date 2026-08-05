<?php

namespace Tests\Unit\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use InternetGuru\LaravelCommon\Traits\AssociationHistory as AssociationHistoryTrait;
use InternetGuru\LaravelCommon\View\Components\AssociationHistory;
use Tests\TestCase;

class AssociationHistoryTestModel extends Model
{
    use AssociationHistoryTrait;

    protected $table = 'association_history_models';

    protected $guarded = [];

    public array $associationHistoryTracked = ['name'];
}

class AssociationHistoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('association_history_models', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('association_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('associable');
            $table->string('column_name');
            $table->string('column_prev_value')->nullable();
            $table->foreignId('author_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_group_is_labeled_with_latest_time_and_entries_are_ascending(): void
    {
        $model = AssociationHistoryTestModel::create([
            'name' => 'third',
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        // Newest first, all within the same 10 minute frame and by the same author.
        $times = ['2026-08-03 07:07:00', '2026-08-03 07:04:00', '2026-08-03 07:01:00'];
        $previous = ['second', 'first', null];
        foreach ($times as $index => $time) {
            $history = $model->associationHistories()->create([
                'column_name' => 'name',
                'column_prev_value' => $previous[$index],
                'author_id' => null,
            ]);
            $history->forceFill(['created_at' => $time, 'updated_at' => $time])->save();
        }

        $component = new AssociationHistory($model->fresh());

        $this->assertCount(2, $component->groups);
        $this->assertTrue($component->groups[1]['is_creation']);

        $group = $component->groups[0];
        $this->assertSame('2026-08-03 07:07:00', $group['time']->toDateTimeString());
        $this->assertSame(
            ['2026-08-03 07:01:00', '2026-08-03 07:04:00', '2026-08-03 07:07:00'],
            array_map(fn ($history) => $history->created_at->toDateTimeString(), $group['entries']),
        );
    }

    public function test_entries_written_within_the_same_second_keep_their_insertion_order(): void
    {
        $model = AssociationHistoryTestModel::create([
            'name' => null,
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        // Two updates in a row ("added", then "removed"), all entries stamped
        // with the very same second.
        $previous = [null, 'added'];
        foreach ($previous as $prevValue) {
            $history = $model->associationHistories()->create([
                'column_name' => 'name',
                'column_prev_value' => $prevValue,
                'author_id' => null,
            ]);
            $history->forceFill([
                'created_at' => '2026-08-03 07:27:00',
                'updated_at' => '2026-08-03 07:27:00',
            ])->save();
        }

        $component = new AssociationHistory($model->fresh());

        $group = $component->groups[0];
        $this->assertSame(
            [[null, 'added'], ['added', '']],
            array_map(
                fn ($history) => [$history->column_prev_value, $history->new_value],
                $group['entries'],
            ),
        );
    }

    public function test_creation_only_group_uses_model_created_at(): void
    {
        $model = AssociationHistoryTestModel::create([
            'name' => 'initial',
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        $component = new AssociationHistory($model->fresh());

        $this->assertCount(1, $component->groups);
        $this->assertTrue($component->groups[0]['is_creation']);
        $this->assertSame('2026-08-03 06:00:00', $component->groups[0]['time']->toDateTimeString());
    }
}
