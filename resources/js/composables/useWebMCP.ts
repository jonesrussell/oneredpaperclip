
import * as CampaignApi from '@/actions/App/Http/Controllers/Api/CampaignApiController';
import * as OfferApi from '@/actions/App/Http/Controllers/Api/OfferApiController';
import * as TradeApi from '@/actions/App/Http/Controllers/Api/TradeApiController';
import type { ModelContextTool } from '@/types/webmcp';

function getCsrfToken(): string | null {
    if (typeof document === 'undefined') {
        return null;
    }
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    if (!match) {
        return null;
    }
    try {
        return decodeURIComponent(match[1]);
    } catch {
        return match[1];
    }
}

type RouteDef = { url: string; method: string };

async function apiFetch(route: RouteDef, body?: Record<string, unknown>): Promise<Response> {
    const headers: Record<string, string> = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
    const method = route.method.toUpperCase();
    if (method !== 'GET' && method !== 'HEAD') {
        const csrf = getCsrfToken();
        if (csrf) {
            headers['X-XSRF-TOKEN'] = csrf;
        }
    }
    return fetch(route.url, {
        body: body ? JSON.stringify(body) : undefined,
        credentials: 'include',
        headers,
        method,
    });
}

async function parseJsonResponse(response: Response): Promise<unknown> {
    const text = await response.text();
    if (!text) {
        return null;
    }
    try {
        return JSON.parse(text);
    } catch {
        return { error: text };
    }
}

function buildTools(): ModelContextTool[] {
    return [
        {
            name: 'list_public_campaigns',
            description:
                'List publicly visible, active trade-up campaigns for browsing. Returns campaign id, title, status, and current/goal item summaries.',
            inputSchema: {
                type: 'object',
                properties: {
                    limit: { type: 'number', description: 'Max number of campaigns to return (default 15, max 50)' },
                },
            },
            annotations: { readOnlyHint: true },
            async execute(input) {
                const limit = typeof input.limit === 'number' ? input.limit : 15;
                const route = CampaignApi.index({ query: { limit } });
                const response = await apiFetch(route);
                if (!response.ok) {
                    return { error: response.status === 401 ? 'User must be logged in' : await response.text(), status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'get_campaign',
            description:
                'Get one campaign by ID: title, status, current and goal items, and pending offer count.',
            inputSchema: {
                type: 'object',
                properties: { campaign_id: { type: 'number', description: 'Campaign ID' } },
                required: ['campaign_id'],
            },
            annotations: { readOnlyHint: true },
            async execute(input) {
                const campaignId = Number(input.campaign_id);
                if (!Number.isFinite(campaignId)) {
                    return { error: 'campaign_id must be a number' };
                }
                const route = CampaignApi.show({ campaign: campaignId });
                const response = await apiFetch(route);
                if (!response.ok) {
                    return { error: await response.text(), status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'my_campaigns',
            description: "List the current user's campaigns. Requires authentication.",
            inputSchema: {
                type: 'object',
                properties: {
                    limit: { type: 'number', description: 'Max number to return (default 15, max 50)' },
                },
            },
            annotations: { readOnlyHint: true },
            async execute(input) {
                const limit = typeof input.limit === 'number' ? input.limit : 15;
                const route = CampaignApi.mine({ query: { limit } });
                const response = await apiFetch(route);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in', campaigns: [] };
                }
                if (!response.ok) {
                    return { error: await response.text(), status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'create_campaign',
            description:
                'Start a new trade-up campaign with a start item and a goal item. Requires authentication.',
            inputSchema: {
                type: 'object',
                properties: {
                    title: { type: 'string', description: 'Campaign title' },
                    start_item_title: { type: 'string', description: 'Start item title' },
                    start_item_description: { type: 'string', description: 'Start item description' },
                    goal_item_title: { type: 'string', description: 'Goal item title' },
                    goal_item_description: { type: 'string', description: 'Goal item description' },
                    category_id: { type: 'number', description: 'Optional category ID' },
                },
                required: ['start_item_title', 'goal_item_title'],
            },
            async execute(input) {
                const body = {
                    title: input.title ?? null,
                    start_item: {
                        title: String(input.start_item_title ?? ''),
                        description: input.start_item_description ?? null,
                    },
                    goal_item: {
                        title: String(input.goal_item_title ?? ''),
                        description: input.goal_item_description ?? null,
                    },
                    category_id: typeof input.category_id === 'number' ? input.category_id : null,
                };
                const route = CampaignApi.store();
                const response = await apiFetch(route, body as Record<string, unknown>);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in' };
                }
                if (!response.ok) {
                    const data = await parseJsonResponse(response);
                    return { error: (data as { message?: string })?.message ?? await response.text(), status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'submit_offer',
            description:
                'Submit an offer on a campaign\'s current item. Requires authentication.',
            inputSchema: {
                type: 'object',
                properties: {
                    campaign_id: { type: 'number', description: 'Campaign ID' },
                    offered_item_title: { type: 'string', description: 'Title of the item you are offering' },
                    offered_item_description: { type: 'string', description: 'Description of the offered item' },
                },
                required: ['campaign_id', 'offered_item_title'],
            },
            async execute(input) {
                const campaignId = Number(input.campaign_id);
                if (!Number.isFinite(campaignId)) {
                    return { error: 'campaign_id must be a number' };
                }
                const body = {
                    offered_item: {
                        title: String(input.offered_item_title ?? ''),
                        description: input.offered_item_description ?? null,
                    },
                };
                const route = OfferApi.store({ campaign: campaignId });
                const response = await apiFetch(route, body as Record<string, unknown>);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in' };
                }
                if (!response.ok) {
                    const data = await parseJsonResponse(response);
                    return { error: (data as { message?: string })?.message ?? await response.text(), status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'accept_offer',
            description:
                'Accept a pending offer (campaign owner only). Creates a trade. Requires user confirmation.',
            inputSchema: {
                type: 'object',
                properties: { offer_id: { type: 'number', description: 'Offer ID' } },
                required: ['offer_id'],
            },
            async execute(input, client) {
                const offerId = Number(input.offer_id);
                if (!Number.isFinite(offerId)) {
                    return { error: 'offer_id must be a number' };
                }
                const confirmed = await client.requestUserInteraction(async () => {
                    return window.confirm('Accept this offer? A trade will be created for both parties to confirm.');
                });
                if (!confirmed) {
                    return { cancelled: true, message: 'User declined confirmation' };
                }
                const route = OfferApi.accept({ offer: offerId });
                const response = await apiFetch(route);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in' };
                }
                if (!response.ok) {
                    const data = await parseJsonResponse(response);
                    return { error: (data as { message?: string })?.message ?? 'Accept failed', status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'decline_offer',
            description: 'Decline a pending offer (campaign owner only). Requires authentication.',
            inputSchema: {
                type: 'object',
                properties: { offer_id: { type: 'number', description: 'Offer ID' } },
                required: ['offer_id'],
            },
            async execute(input) {
                const offerId = Number(input.offer_id);
                if (!Number.isFinite(offerId)) {
                    return { error: 'offer_id must be a number' };
                }
                const route = OfferApi.decline({ offer: offerId });
                const response = await apiFetch(route);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in' };
                }
                if (!response.ok) {
                    const data = await parseJsonResponse(response);
                    return { error: (data as { message?: string })?.message ?? 'Decline failed', status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
        {
            name: 'confirm_trade',
            description:
                'Confirm a trade (offerer or campaign owner). When both parties have confirmed, the trade completes. Requires user confirmation.',
            inputSchema: {
                type: 'object',
                properties: { trade_id: { type: 'number', description: 'Trade ID' } },
                required: ['trade_id'],
            },
            async execute(input, client) {
                const tradeId = Number(input.trade_id);
                if (!Number.isFinite(tradeId)) {
                    return { error: 'trade_id must be a number' };
                }
                const confirmed = await client.requestUserInteraction(async () => {
                    return window.confirm('Confirm this trade?');
                });
                if (!confirmed) {
                    return { cancelled: true, message: 'User declined confirmation' };
                }
                const route = TradeApi.confirm({ trade: tradeId });
                const response = await apiFetch(route);
                if (response.status === 401 || response.status === 302) {
                    return { error: 'User must be logged in' };
                }
                if (!response.ok) {
                    const data = await parseJsonResponse(response);
                    return { error: (data as { message?: string })?.message ?? 'Confirm failed', status: response.status };
                }
                return parseJsonResponse(response);
            },
        },
    ];
}

/**
 * Register WebMCP tools with the browser when the Model Context Protocol API is available.
 * No-op when navigator.modelContext is undefined (e.g. unsupported or non-secure context).
 */
export function registerWebMCPTools(): void {
    if (typeof navigator === 'undefined' || !('modelContext' in navigator) || !navigator.modelContext) {
        return;
    }
    const tools = buildTools();
    navigator.modelContext.provideContext({ tools });
}
