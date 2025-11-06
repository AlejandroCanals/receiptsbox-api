<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    // TODO --> Validation -->formrequest classes

    public function __construct()
    {
        $this->authorizeResource(Receipt::class, 'receipt');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $receipts = $user->receipts()->latest()->paginate(20);

        return response()->json([
            'status' => 'success',
            'message' => $receipts->isEmpty()
                ? 'There are no receipts yet'
                : 'Receipts obtained correctly',
            'data' => $receipts->items(),
            'meta' => [
                'current_page' => $receipts->currentPage(),
                'last_page' => $receipts->lastPage(),
                'total' => $receipts->total(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = validator($request->all(),[
            'image' => ['required', 'image', 'max:4096'],
        ])->validate();

        $user = $request->user();
        $path = $request->file('image')->store('receipts','public');

        $receipt = $user->receipts()->create([
            'image_path' => $path,
            'status' => 'pending'
        ]);

        dispatch(new \App\Jobs\ProcessReceiptImageJob($receipt));
        
        return response()->json([
            'status' => 'success',
            'message' => 'Receipt uploaded successfully. OCR processing started.',
            'data' => $receipt
        ],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request,Receipt $receipt)
    {
        return response()->json([
            'status' => 'success',
            'data' => $receipt,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Receipt $receipt)
    {
        $data = validator($request->all(),[
            'merchant' => ['nullable','string','max:255'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'image_path' => ['nullable','string','max:255'],
            'status' => ['nullable', 'in:pending,processed,failed'],
            'ocr_data' => ['nullable', 'array'],
            'date' => ['nullable', 'date'],
            
        ])->validate();
        
        $receipt->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'The receipt has been updated successfully',
            'data' => $receipt
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request,Receipt $receipt)
    {
        $receipt->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'The receipt has been deleted'
        ],200);
    }
}
