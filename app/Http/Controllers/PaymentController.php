<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::all();
        return $payments;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate incoming request data
        $request->validate([
            'LRN' => 'required|string',
            'amount_paid' => 'nullable|numeric',
            'proof_payment' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'nullable|string', // Optional field
        ]);

        // Generate a unique OR_number
        $orNumber = $this->generateAndStoreOrNumber();

        // Store the uploaded image file
        $path = $request->file('proof_payment')->store('payments', 'public');

        $payment = Payment::create([
            'LRN' => $request->LRN,
            'OR_number' => $orNumber, 
            'amount_paid' => $request->amount_paid,
            'proof_payment' => $path,
            'description' => $request->description,
        ]);

        return response()->json(['message' => 'Payment proof uploaded successfully!', 'data' => $payment], 201);
    }

    private function generateAndStoreOrNumber()
    {
        do {
            // Generate a random string of 10 characters for OR_number
            $orNumber = strtoupper(Str::random(10));
        } while (Payment::where('OR_number', $orNumber)->exists()); // Ensure uniqueness

        return $orNumber; // Return the unique OR_number
    }

    public function getPaymentDetails($lrn)
    {
    $payment = Payment::where('LRN', $lrn)->first(); 

    if (!$payment) {
        return response()->json(['message' => 'Payment not found'], 404);
    }

    return response()->json([
        'date_of_payment' => $payment->created_at->format('Y-m-d'), 
        'amount_paid' => $payment->amount_paid,
    ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        //
    }
}
