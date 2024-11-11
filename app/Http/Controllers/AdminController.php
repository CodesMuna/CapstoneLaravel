<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admin = Admin::all();
        return $admin;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        
        $formfields = $request->validate([
            'fname' => 'required|max:50',
            'lname' => 'required|max:50',
            'mname' => 'required|max:50',
            'role' => 'required',
            'address' => 'required|max:255',
            'email' => 'required',
            'password' => 'required',
        ]);

        $formfields['password'] = Hash::make($formfields['password']);
        // return $request;
        $admin = Admin::create($formfields);
        return response()->json($admin, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        return $admin;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        $formfields = $request->validate([
            'admin_id' => 'required|integer|unique:admins,admin_id',
            'fname' => 'required|max:50',
            'lname' => 'required|max:50',
            'mname' => 'required|max:50',
            'role' => 'required',
            'address' => 'required|max:255',
            'email' => 'required',
            'password' => 'required',
        ]);

        $admin->update($formfields);
        return $admin;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        $admin->delete();

        return "Deleted";
    }
}
