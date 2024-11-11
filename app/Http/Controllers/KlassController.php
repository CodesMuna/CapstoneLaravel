<?php

namespace App\Http\Controllers;

use App\Models\Klass;
use App\Models\Subject;
use App\Models\Admin;
use Illuminate\Http\Request;
use App\Http\Requests\StoreKlassRequest;
use App\Http\Requests\UpdateKlassRequest;   

class KlassController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $klass = Klass::with(['subject', 'admin'])->get(); 
        return response()->json($klass);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'admin_id' => 'required|exists:admins,admin_id',
            'subject_id' => 'required|exists:subjects,subject_id',
            'section' => 'required|string|max:255',
            'room' => 'required|string|max:255',
            'schedule' => 'required|string|max:255',
        ]);

        $klass = Klass::create($validateData);
        return response()->json($klass, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Klass $klass)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateKlassRequest $request, Klass $klass)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Klass $klass)
    {
        //
    }
}
