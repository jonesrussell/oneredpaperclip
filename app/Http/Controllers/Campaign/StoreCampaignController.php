<?php

namespace App\Http\Controllers\Campaign;

use App\Actions\CreateCampaign;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use Illuminate\Http\RedirectResponse;

class StoreCampaignController extends Controller
{
    /**
     * Store a newly created campaign.
     */
    public function __invoke(StoreCampaignRequest $request, CreateCampaign $createCampaign): RedirectResponse
    {
        $createCampaign($request->validated(), $request->user());

        return to_route('dashboard');
    }
}
