<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmTrade;
use App\Models\Trade;
use Illuminate\Http\RedirectResponse;

class TradeController extends Controller
{
    /**
     * Confirm the trade (offerer or challenge owner). When both have confirmed,
     * trade is completed and challenge current item is advanced.
     */
    public function confirm(Trade $trade, ConfirmTrade $confirmTrade): RedirectResponse
    {
        $this->authorize('confirm', $trade);

        $confirmTrade($trade, request()->user());

        return redirect()->route('challenges.show', $trade->challenge);
    }
}
