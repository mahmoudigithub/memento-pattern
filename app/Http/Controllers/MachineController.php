<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMachineRequest;
use App\Http\Requests\UpdateMachineRequest;
use App\Models\Machine;
use App\Services\Machine\MachineService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function __construct(
        private MachineService $service
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index():View
    {
        $machines = Machine::all();

        return view('machine.index', compact('machines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create():View
    {
        return view('machine.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMachineRequest $request)
    {
        return $this->service->create($request) ?
            redirect(route('machine.index'))->with('alert-success', 'دستگاه جدید با موفقیت ایجاد شد !') :
            redirect()->back()->with('alert-danger', 'وجود خطا در سرور !');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Machine $machine)
    {
        return view('machine.edit', compact('machine'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMachineRequest $request, Machine $machine)
    {
        return $this->service->update($machine, $request) ?
            redirect()->route('machine.index')
                ->with('alert-success', "دستگاه {$machine->name} با موفقیت ویرایش شد ."):
            redirect()->back()
                ->with('alert-danger', 'مشکلی پیش آمده است !');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Machine $machine)
    {
        return $this->service->delete($machine) ?
            redirect()->route('machine.index')
                ->with('alert-success', 'دستگاه با موفقیت حذف شد !') :
            redirect()->back()
                ->with('alert-danger', 'مشکلی پیش آمده است !');
    }
}
