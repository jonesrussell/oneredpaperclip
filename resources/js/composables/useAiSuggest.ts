import { ref } from 'vue';
import { aiSuggest } from '@/actions/App/Http/Controllers/ChallengeController';
import { getCsrfToken } from '@/lib/utils';

export type AiSuggestContext = 'start_item' | 'goal_item' | 'story';

interface AiSuggestResponse {
    suggestion?: string;
    message?: string;
}

export function htmlToPlainText(html: string): string {
    if (!html) return '';
    return html
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

export function useAiSuggest() {
    const loading = ref<AiSuggestContext | null>(null);
    const error = ref<string | null>(null);

    async function requestSuggestion(
        context: AiSuggestContext,
        currentHtml: string,
        titleHint: string,
    ): Promise<string | null> {
        error.value = null;
        loading.value = context;

        try {
            const headers: Record<string, string> = {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            };
            const csrf = getCsrfToken();
            if (csrf) headers['X-XSRF-TOKEN'] = csrf;

            const res = await fetch(aiSuggest.url(), {
                method: 'POST',
                credentials: 'include',
                headers,
                body: JSON.stringify({
                    context,
                    current_text: htmlToPlainText(currentHtml),
                    title_hint: titleHint || undefined,
                }),
            });

            const data = (await res.json()) as AiSuggestResponse;

            if (!res.ok) {
                error.value = data.message ?? 'Request failed';
                return null;
            }

            const suggestion = data.suggestion ?? '';
            return suggestion ? `<p>${suggestion}</p>` : '';
        } catch {
            error.value = 'Something went wrong. Try again.';
            return null;
        } finally {
            loading.value = null;
        }
    }

    return {
        loading,
        error,
        requestSuggestion,
    };
}
