<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller responsible for updating order payment statuses.
 */
class OrderPaymentStatusController extends Controller
{
    /**
     * Update the payment status for the specified order.
     *
     * @param  Request  $request
     * @param  Order  $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(['unpaid', 'partial', 'paid'])],
        ]);

        $targetStatus = $validated['payment_status'];

        if ($targetStatus === 'paid') {
            $paymentsTotal = (float) $order->payments()->sum('amount');
            $grandTotal = (float) $order->grand_total;
            $difference = round($grandTotal - $paymentsTotal, 4);

            if ($difference > 0) {
                $order->payments()->create([
                    'paid_at' => now(),
                    'method' => 'cash',
                    'amount' => number_format($difference, 4, '.', ''),
                ]);
            }
        }

        $order->update([
            'payment_status' => $targetStatus,
        ]);

        $order->load(['customer:id,name']);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'code' => $order->code,
                'customer' => [
                    'name' => $order->customer?->name,
                ],
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'grand_total' => $order->grand_total,
                'delivered_at' => $order->delivered_at,
            ],
        ]);
    }
}
