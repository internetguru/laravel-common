<?php

namespace Tests\Unit\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    public array $associationHistoryTracked = ['name', 'owner_id', 'created_by'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(AssociationHistoryTestUser::class, 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(AssociationHistoryTestUser::class, 'created_by');
    }
}

class AssociationHistoryTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
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
            $table->foreignId('owner_id')->nullable();
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

    public function test_foreign_key_values_are_shown_as_related_model_labels(): void
    {
        AssociationHistoryTestUser::create(['id' => 4, 'name' => 'Oda O\'Kon']);
        AssociationHistoryTestUser::create(['id' => 8, 'name' => 'Shany Runolfsdottir']);

        $model = AssociationHistoryTestModel::create([
            'name' => 'reservation',
            'owner_id' => 8,
            'created_by' => 4,
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        $history = $model->associationHistories()->create([
            'column_name' => 'owner_id',
            'column_prev_value' => '4',
            'author_id' => null,
        ]);
        $history->forceFill([
            'created_at' => '2026-08-03 07:00:00',
            'updated_at' => '2026-08-03 07:00:00',
        ])->save();

        $component = new AssociationHistory($model->fresh());

        $entry = $component->groups[0]['entries'][0];
        $this->assertSame('Oda O\'Kon', $entry->column_prev_value_translated);
        $this->assertSame('Shany Runolfsdottir', $entry->new_value_translated);
    }

    public function test_creation_author_comes_from_history_when_the_created_by_column_changed(): void
    {
        AssociationHistoryTestUser::create(['id' => 4, 'name' => 'Oda O\'Kon']);
        AssociationHistoryTestUser::create(['id' => 8, 'name' => 'Shany Runolfsdottir']);

        // Created by user 4, later reassigned to user 8.
        $model = AssociationHistoryTestModel::create([
            'name' => 'reservation',
            'created_by' => 8,
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        $history = $model->associationHistories()->create([
            'column_name' => 'created_by',
            'column_prev_value' => '4',
            'author_id' => null,
        ]);
        $history->forceFill([
            'created_at' => '2026-08-03 09:00:00',
            'updated_at' => '2026-08-03 09:00:00',
        ])->save();

        $component = new AssociationHistory($model->fresh());

        $creationGroup = collect($component->groups)->firstWhere('is_creation', true);
        $this->assertSame(4, $creationGroup['author_id']);
        $this->assertSame('Oda O\'Kon', $creationGroup['author_name']);
    }

    public function test_unresolvable_foreign_key_values_fall_back_to_the_raw_value(): void
    {
        $model = AssociationHistoryTestModel::create([
            'name' => 'reservation',
            'owner_id' => 99,
            'created_at' => '2026-08-03 06:00:00',
            'updated_at' => '2026-08-03 06:00:00',
        ]);

        $history = $model->associationHistories()->create([
            'column_name' => 'owner_id',
            'column_prev_value' => '98',
            'author_id' => null,
        ]);
        $history->forceFill([
            'created_at' => '2026-08-03 07:00:00',
            'updated_at' => '2026-08-03 07:00:00',
        ])->save();

        $component = new AssociationHistory($model->fresh());

        $entry = $component->groups[0]['entries'][0];
        $this->assertSame('98', $entry->column_prev_value_translated);
        $this->assertSame('99', $entry->new_value_translated);
    }
}
