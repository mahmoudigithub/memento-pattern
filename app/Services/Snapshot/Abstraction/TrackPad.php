<?php

namespace App\Services\Snapshot\Abstraction;

use App\Models\Snapshot;
use App\Services\Snapshot\SnapshotService;

class TrackPad
{
    /**
     * Bridge
     *
     * @param SnapshotService $caretaker
     */
    public function __construct(
        private SnapshotService $caretaker
    ){}

    /**
     * Undo changes operations
     *
     * @return bool
     */
    public function undo(): bool
    {

    }

    /**
     * Redo changes operations
     *
     * @return bool
     */
    public function redo(): bool
    {

    }

    /**
     * Removes all snapshots that have greater id compare current snapshot
     *
     * @return bool
     */
    public function forgetHistoryAfterCurrent(): bool
    {
        $machine = $this->caretaker->getMachine();

         $current = $machine->currentSnapshot();

         if(!$current)
             return false;


         return (bool)$machine->snapshots()
             ->where('created_at', '>', $current->created_at)
             ->where('id', '>', $current->id)
             ->delete();
    }

    /**
     * Sets is_current field value 0 if there is current snapshot for machine
     *
     * @return void
     */
    public function unmarkCurrentSnapshot():void
    {
        $machine = $this->caretaker->getMachine();

        $machine->snapshots()
            ->where('is_current', '!=', '0')
            ->update(['is_current' => '0']);
    }
}