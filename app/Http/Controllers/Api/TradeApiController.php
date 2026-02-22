<?php

namespace App\Http\Controllers\Api;

use App\Actions\ConfirmTrade;
use App\Http\Controllers\Controller;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;

class TradeApiController extends Controller
{
    /**
     * Confirm a trade (offerer or campaign owner). Authenticated; authorization in controller.
     */
    public function confirm(Trade $trade, ConfirmTrade $confirmTrade): JsonResponse
    {
        $this->authorize('confirm', $trade);

        $confirmTrade($trade, request()->user());

        return response()->json([
            'message' => 'Trade confirmed.',
            'trade_id' => $trade->id,
            'status' => $trade->fresh()->status->value,
        ]);
    }
}
