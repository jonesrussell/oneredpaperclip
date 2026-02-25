<?php

namespace App\Actions;

use App\Ai\Agents\SuggestCampaignTextAgent;

class SuggestCampaignText
{
    /**
     * Get an AI suggestion for campaign/item text.
     *
     * @param  array{context: string, current_text?: string, title_hint?: string}  $input
     */
    public function __invoke(array $input): string
    {
        $prompt = $this->buildPrompt($input);

        $response = SuggestCampaignTextAgent::make()->prompt($prompt);

        $text = trim($response->text);

        if ($text === '') {
            throw new \RuntimeException('AI returned an empty suggestion. Please try again.');
        }

        return $text;
    }

    /**
     * @param  array{context: string, current_text?: string, title_hint?: string}  $input
     */
    private function buildPrompt(array $input): string
    {
        $context = $input['context'];
        $current = $input['current_text'] ?? '';
        $title = $input['title_hint'] ?? '';

        $parts = [];

        if ($context === 'start_item') {
            $parts[] = 'Write a 1-sentence item description (max 20 words) for a trade-up start item.';
            if ($title !== '') {
                $parts[] = "Item title: {$title}.";
            }
        } elseif ($context === 'goal_item') {
            $parts[] = 'Write a 1-sentence item description (max 20 words) for a trade-up dream/goal item.';
            if ($title !== '') {
                $parts[] = "Item title: {$title}.";
            }
        } else {
            $parts[] = 'Write a campaign story (1–2 sentences, max 40 words) explaining why the user is doing this trade-up.';
        }

        if ($current !== '') {
            $parts[] = "Current text to improve or expand:\n{$current}";
            $parts[] = 'Provide an improved or expanded version only.';
        } else {
            $parts[] = 'Provide a new suggestion.';
        }

        return implode("\n\n", $parts);
    }
}
