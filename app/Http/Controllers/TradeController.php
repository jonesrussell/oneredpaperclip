<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmTrade;
use App\Models\Trade;
use Illuminate\Http\RedirectResponse;

class TradeController extends Controller
{
    /**
     * Confirm the trade (offerer or campaign owner). When both have confirmed,
     * trade is completed and campaign current item is advanced.
     */
    public function confirm(Trade $trade, ConfirmTrade $confirmTrade): RedirectResponse
    {
        $this->authorize('confirm', $trade);

        $confirmTrade($trade, request()->user());

        return redirect()->route('campaigns.show', $trade->campaign);
    }
}
