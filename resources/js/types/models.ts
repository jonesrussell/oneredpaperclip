export type ChallengeStatus = 'draft' | 'active' | 'completed' | 'paused';
export type OfferStatus =
    | 'pending'
    | 'accepted'
    | 'declined'
    | 'withdrawn'
    | 'expired';
export type TradeStatus = 'pending_confirmation' | 'completed' | 'disputed';

export type ItemSummary = {
    id: number;
    title: string;
    image_url?: string | null;
};

export type OfferSummary = {
    id: number;
    status: OfferStatus;
    message?: string | null;
    from_user: { id: number; name: string } | null;
    offered_item: ItemSummary | null;
};

export type TradeSummary = {
    id: number;
    status: TradeStatus;
    position: number;
    offered_item: ItemSummary | null;
    offerer: { id: number; name: string } | null;
    owner_confirmed: boolean;
    offerer_confirmed: boolean;
};

export type CommentSummary = {
    id: number;
    body: string;
    user: { id: number; name: string } | null;
    created_at: string;
};
