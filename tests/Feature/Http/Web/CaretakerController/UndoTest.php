<?php

namespace Tests\Feature\Http\Web\CaretakerController;

use App\Factory\MementoObject;
use App\Models\Machine;
use App\Models\Snapshot;
use Tests\Feature\Http\Web\CaretakerController\HasMockedMementoObject;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use PHPUnit\Framework\MockObject\Exception;
use Tests\TestCase;

class UndoTest extends TestCase
{
    use HasMockedMementoObject;

    /**
     * Undo route name
     */
    private const ROUTE_NAME = 'caretaker.undo';

    protected function setUp(): void
    {
        parent::setUp();

        $this->injectMementoObjectMockToContainer();
    }

    /**
     * Since that undo operation needs non-empty snapshot table,
     * this method is in charge to fill the table
     *
     * @param Machine $machine
     * @return Collection
     */
    private function seedSnapshots(Machine $machine):Collection
    {
        return Snapshot::factory()->for($machine, 'snapshotable')->count(rand(1, 10))->create();
    }


    /**
     * @return void
     * @throws Exception
     */
    public function test_makes_new_snapshot_when_there_is_no_any_current_snapshot_in_the_table()
    {
        $machine = Machine::factory()->create();

        $snapshots = $this->seedSnapshots($machine);

        $snapshotsCount = count($snapshots);

        $this->assertDatabaseCount($snapshots[0]->getTable(), $snapshotsCount);

        $res = $this->post(route(self::ROUTE_NAME, $machine));

        $res->assertRedirect();

        $snapshotsCount = $snapshotsCount + 1;

        $this->assertDatabaseCount($snapshots[0]->getTable(), ($snapshotsCount));
    }

    /**
     * @return void
     * @throws Exception
     */
    public function test_does_not_make_new_snapshot_when_there_is_a_current_snapshot_in_the_table()
    {
        $machine = Machine::factory()->create();

        $snapshotsCount = count($this->seedSnapshots($machine));

        $this->travel(1)->hour();

        Snapshot::factory()->for($machine, 'snapshotable')->current()->create();

        $this->travel(1)->hour();

        $snapshotsCount += count($this->seedSnapshots($machine)) + 1;

        $this->assertDatabaseCount('snapshots', $snapshotsCount);

        $res = $this->post(route(self::ROUTE_NAME, $machine));

        $res->assertRedirect();

        $this->assertDatabaseCount('snapshots', $snapshotsCount);
    }

    /**
     * @return void
     * @throws Exception
     */
    public function test_set_first_snapshot_before_current_and_make_that_current()
    {
        $machine = Machine::factory()->create();

        $shouldSet = Snapshot::factory()->for($machine, 'snapshotable')->create();

        $this->travel(1)->hour();

        Snapshot::factory()->for($machine, 'snapshotable')->current()->create();

        $this->travel(1)->hour();

        $this->seedSnapshots($machine);

        $res = $this->post(route(self::ROUTE_NAME, $machine));

        $res->assertRedirect();

        $updatedMachine = Machine::find($machine->id);

        $this->assertNotSame($machine->getAttributes(), $updatedMachine->getAttributes());

        $shouldSet = Snapshot::find($shouldSet->id);

        $this->assertTrue((bool)$shouldSet->is_current);
    }

    /**
     * @return void
     */
    public function test_makes_snapshot_when_there_is_no_any_current_snapshot_and_then_set_last_snapshot_before_new_values_to_machine()
    {
        $machine = Machine::factory()->create();

        $snapshotsCount = count($this->seedSnapshots($machine));

        $res = $this->post(route(self::ROUTE_NAME, $machine));

        $res->assertRedirect();

        $snapshotsCount += 1;

        $this->assertDatabaseCount('snapshots', $snapshotsCount);

        $updatedMachine = Machine::find($machine->id);

        $this->assertNotSame($machine->getAttributes(), $updatedMachine->getAttributes());
    }

    /**
     * @return void
     */
    public function test_set_0_for_is_current_field_of_snapshot_that_defined_as_current_before_undo_operation_then_set_1_for_restored_snapshot()
    {
        $machine = Machine::factory()->create();

        $this->seedSnapshots($machine);

        $this->travel(1)->minute();

        $shouldBeCurrentAfterUndo = Snapshot::factory()->for($machine, 'snapshotable')->create();

        $this->travel(1)->minute();

        $currentSnapshotBeforeUndo = Snapshot::factory()->for($machine, 'snapshotable')->current()->create();

        $this->travel(1)->minute();

        $this->seedSnapshots($machine);

        $res = $this->post(route(self::ROUTE_NAME, $machine));

        $res->assertRedirect();

        $this->assertDatabaseHas($shouldBeCurrentAfterUndo->getTable(), [
            'id' => $shouldBeCurrentAfterUndo->id,
            'is_current' => 1,
        ]);

        $this->assertDatabaseHas($currentSnapshotBeforeUndo->getTable(), [
            'id' => $currentSnapshotBeforeUndo->id,
            'is_current' => 0,
        ]);
    }
}
