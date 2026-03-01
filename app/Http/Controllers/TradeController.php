<?php

namespace App\Http\Controllers;

use App\Actions\ConfirmTrade;
use App\Actions\UpdateTrade;
use App\Enums\TradeStatus;
use App\Http\Requests\UpdateTradeRequest;
use App\Models\Trade;
use Illuminate\Http\RedirectResponse;

class TradeController extends Controller
{
    /**
     * Update the traded item (title, description, image).
     */
    public function update(UpdateTradeRequest $request, Trade $trade, UpdateTrade $updateTrade): RedirectResponse
    {
        $updateTrade($trade, $request->validated());

        return redirect()->route('challenges.show', $trade->challenge)
            ->with('success', 'Trade item updated.');
    }

    /**
     * Confirm the trade (offerer or challenge owner). Owner confirmation
     * completes the trade and advances the challenge current item.
     */
    public function confirm(Trade $trade, ConfirmTrade $confirmTrade): RedirectResponse
    {
        $this->authorize('confirm', $trade);

        $trade = $confirmTrade($trade, request()->user());

        $message = $trade->status === TradeStatus::Completed
            ? 'Trade complete!'
            : 'Trade confirmed! Waiting for the other party.';

        return redirect()->route('challenges.show', $trade->challenge)
            ->with('success', $message);
    }
}
