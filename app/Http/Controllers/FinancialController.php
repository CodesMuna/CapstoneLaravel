<?php

namespace App\Http\Controllers;

use App\Models\Financial;
use App\Http\Requests\StoreFinancialRequest;
use App\Http\Requests\UpdateFinancialRequest;

class FinancialController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFinancialRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Financial $financial)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFinancialRequest $request, Financial $financial)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Financial $financial)
    {
        //
    }
}
