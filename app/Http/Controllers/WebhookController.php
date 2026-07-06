<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Models\ShopOrder;
use App\Utils\JDBUtil;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function apiUpdateBcel(Request $request): JsonResponse
    {
        try {
            Log::info('BCEL webhook', $request->all());

            $payload = $request->input('payload');
            $receivedSignature = $request->input('signature');

            $paymentData = json_decode($payload, true);
            $billNumber = $paymentData['uuid'];
            $paymentAmount = (float) $paymentData['amount'];
            $paymentRefNo = $paymentData['fccref'];
            $paymentDateTime = $paymentData['txtime'];

            // ================================================================================
            // VERIFY SIGNATURE
            // ================================================================================

            $appKey = config('app.key');
            $calculatedSignature = hash_hmac('sha256', $payload, $appKey);

            if (!hash_equals($calculatedSignature, $receivedSignature)) {
                Log::error('Invalid signature', [
                    'received'   => $receivedSignature,
                    'calculated' => $calculatedSignature,
                ]);
                return response()->json([
                    'message' => 'Invalid signature',
                    'received'   => $receivedSignature,
                    'calculated' => $calculatedSignature,
                ], 400);
            }

            Log::info('Valid signature');

            // ================================================================================
            // CHECK ORDER
            // ================================================================================

            $order = ShopOrder::query()
                ->where('payment_uuid', '=', $billNumber)
                ->first();

            if (!$order) {
                Log::info('Order not found. Bill Number: ' . $billNumber);
                return response()->json(['message' => 'Order not found']);
            }

            if ($order->payment_status !== 'pending') {
                Log::info('Order is not pending', $order->toArray());
                return response()->json(['message' => 'Order is not pending. ID: '. $order->id]);
            }

            if ((float) $order->payment_amount !== $paymentAmount) {
                Log::info('Invalid payment amount', [
                    'order'                => $order->toArray(),
                    'order_payment_amount' => (float) $order->payment_amount,
                    'bank_payment_amount'  => $paymentAmount,
                ]);
                return response()->json(['message' => 'Invalid payment amount. Order ID: '. $order->id]);
            }

            // ================================================================================
            // UPDATE PAYMENT
            // ================================================================================

            DB::beginTransaction();

            $paidAt = CarbonImmutable::createFromFormat('d/m/Y H:i:s', $paymentDateTime)->format('Y-m-d H:i:s');
            $payment = $order->payment()->create([
                'tenant_id'         => $order->tenant_id,
                'channel'           => 'onepay',
                'merchant_provider' => 'bcel',
                'merchant_id'       => config('custom.bcel_qr_mc_id'),
                'amount'            => $paymentAmount,
                'xref'              => $billNumber,
                'ref'               => $paymentRefNo,
                'reconciled_at'     => $paidAt,
                'type'              => 'payment',
                'response'          => $paymentData,
            ]);

            $order->update([
                'payment_status'        => 'paid',
                'payment_reconciled_at' => $paidAt,
            ]);

            DB::commit();

            Log::info('Update order to paid success', [
                'order'   => [
                    'id'                    => $order->id,
                    'payment_reconciled_at' => $order->payment_reconciled_at,
                ],
                'payment' => $payment->toArray(),
            ]);

            $event = event(new OrderPaid($order->id));

            Log::info('Dispatched OrderPaid event', [
                'event' => $event,
            ]);

            return response()->json(['message' => 'Update order to paid success. ID: '. $order->id]);

        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Update payment error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Update failed']);

        }
    }

    public function apiUpdateJdb(Request $request): JsonResponse
    {
        try {
            Log::info('JDB webhook', $request->all());

            $billNumber = $request->input('billNumber');

            // ================================================================================
            // INQUIRY PAYMENT
            // ================================================================================

            $paymentInquiry = JDBUtil::JDBQRCheckTransaction($billNumber);

            if ($paymentInquiry['success'] === false) {
                Log::info('Order is not paid. Bill number: ' . $billNumber);
                return response()->json(['message' => 'Order is not paid. Bill number: ' . $billNumber]);
            }

            $paymentAmount = $paymentInquiry['data']['txnAmount'];
            $paymentRefNo = $paymentInquiry['data']['refNo'];
            $paymentDateTime = $paymentInquiry['data']['txnDateTime'];

            // ================================================================================
            // CHECK ORDER
            // ================================================================================

            $order = ShopOrder::query()
                ->where('payment_uuid', '=', $billNumber)
                ->first();

            if (!$order) {
                Log::info('Order not found. Bill Number: ' . $billNumber);
                return response()->json(['message' => 'Order not found']);
            }

            if ($order->payment_status !== 'pending') {
                Log::info('Order is not pending', $order->toArray());
                return response()->json(['message' => 'Order is not pending. ID: '. $order->id]);
            }

            if ((float) $order->payment_amount !== (float) $paymentAmount) {
                Log::info('Invalid payment amount', [
                    'order'                => $order->toArray(),
                    'order_payment_amount' => (float) $order->payment_amount,
                    'bank_payment_amount'  => (float) $paymentAmount,
                ]);
                return response()->json(['message' => 'Invalid payment amount. ID: '. $order->id]);
            }

            // ================================================================================
            // UPDATE PAYMENT
            // ================================================================================

            DB::beginTransaction();

            $payment = $order->payment()->create([
                'tenant_id'         => $order->tenant_id,
                'channel'           => 'laoqr',
                'merchant_provider' => 'jdb',
                'merchant_id'       => config('custom.jdb_qr_mc_id'),
                'amount'            => $paymentAmount,
                'xref'              => $billNumber,
                'ref'               => $paymentRefNo,
                'reconciled_at'     => $paymentDateTime,
                'type'              => 'payment',
                'response'          => $paymentInquiry,
            ]);

            $order->update([
                'payment_status'        => 'paid',
                'payment_reconciled_at' => $paymentDateTime,
            ]);

            DB::commit();

            Log::info('Update order to paid success', [
                'order'   => [
                    'id'                    => $order->id,
                    'payment_reconciled_at' => $order->payment_reconciled_at,
                ],
                'payment' => $payment->toArray(),
            ]);

            $event = event(new OrderPaid($order->id));

            Log::info('Dispatched OrderPaid event', [
                'event' => $event,
            ]);

            return response()->json(['message' => 'Update order to paid success. ID: '. $order->id]);

        } catch (Exception $e) {

            DB::rollBack();

            Log::error('Update JDB payment error', [
                'message' => $e->getMessage(),
                'stack'   => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Update failed']);

        }
    }
}
