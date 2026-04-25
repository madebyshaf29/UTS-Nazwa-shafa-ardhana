<?php
 
namespace App\Http\Controllers;
 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // <-- WAJIB
 
class MidtransCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // cek data dari Midtrans
        Log::info('MIDTRANS CALLBACK:', $request->all());
 
        return response()->json([
            'status' => 'success'
        ]);
    }
}