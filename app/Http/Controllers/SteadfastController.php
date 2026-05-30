<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteadfastController extends Controller
{
    public function steadfast_update(Request $request)
    {
        return back()->with('warning', translate('Steadfast credentials are now managed from the .env file.'));
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
        ]);

        $order = Order::findOrFail($request->order_id);

        try {
            $result = (new SteadfastService())->createBooking($order);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'consignment' => $result['consignment'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('steadfast_create_order_request_failed', [
                'order_id' => $order->id,
                'order_code' => $order->code,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function deliveryStatus()
    {
        return response()->json([
            'success' => false,
            'message' => 'Steadfast delivery status sync is not implemented yet.',
        ], 501);
    }
}

