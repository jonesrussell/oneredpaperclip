<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

#[UseCheapestModel]
#[MaxTokens(100)]
class SuggestCampaignTextAgent implements Agent
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You write text for a trade-up campaign platform where users trade items toward a goal.
            Rules:
            - Reply with ONLY the suggested text. No preamble, labels, or quotes.
            - Item descriptions: 1 sentence, max 20 words. Highlight what makes it appealing to trade for.
            - Campaign stories: 1–2 sentences, max 40 words. Explain the motivation behind the trade-up journey.
            - Be specific and vivid, not generic. Use the item title when provided.
            - Never repeat the item title as the entire suggestion.
            INSTRUCTIONS;
    }
}
