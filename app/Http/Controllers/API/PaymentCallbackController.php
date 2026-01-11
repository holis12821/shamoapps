<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Services\Midtrans\MidtransCallbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    /**
     * Midtrans Payment Notification Callback
     */
    public function handle(
        Request $request,
        MidtransCallbackService $service
    ) {
        Log::info('Midtrans Callback Incoming', [
            'payload' => $request->all(),
        ]);

        $service->handle($request->all());


        /**
         * MUST return 200 OK
         * Otherwise Midtrans will retry
         */

        return response()->json(['status' => 'ok']);
    }
}
