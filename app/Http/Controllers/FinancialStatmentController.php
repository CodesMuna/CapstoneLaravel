<?php

namespace App\Http\Controllers;

use App\Models\Financial_Statment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreFinancial_StatmentRequest;
use App\Http\Requests\UpdateFinancial_StatmentRequest;

class FinancialStatmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $financial_statments = Financial_Statment::all();
        return $financial_statments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'LRN' => 'required|exists:students,LRN',
            'filename' => 'required|string|max:255',
            'date_uploaded' => 'required|date',
        ]);

        $financial_statments = Financial_Statment::create($validateData);
        return response()->json($financial_statments, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Financial_Statment $financial_Statment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFinancial_StatmentRequest $request, Financial_Statment $financial_Statment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Financial_Statment $financial_Statment)
    {
        //
    }
}
