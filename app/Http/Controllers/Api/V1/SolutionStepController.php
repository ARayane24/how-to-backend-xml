<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StoreSolutionStepRequest;
use App\Http\Requests\UpdateSolutionStepRequest;
use App\Models\SolutionStep;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\StepCollection;
use App\Http\Resources\V1\StepResource;

class SolutionStepController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new StepCollection(SolutionStep::paginate());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSolutionStepRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SolutionStep $solutionStep)
    {
        return new StepResource($solutionStep);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SolutionStep $solutionStep)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSolutionStepRequest $request, SolutionStep $solutionStep)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SolutionStep $solutionStep)
    {
        //
    }
}
