export type Money = {
    amount: number;
    currency: string;
    decimal: string;
    formatted: string;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    links: { url: string | null; label: string; active: boolean }[];
};

export type WorkspaceProps = {
    id: number;
    name: string;
    currency: string;
    timezone: string;
    brand_color: string;
    plan: string;
    on_trial: boolean;
    trial_ends_at: string | null;
    role: 'owner' | 'admin' | 'member' | null;
    can_manage: boolean;
    is_owner: boolean;
    all: { id: number; name: string }[];
};

export type ChangeRequestRow = {
    id: number;
    reference: string;
    title: string | null;
    status: string;
    status_label: string;
    price: Money | null;
    project: { id: number; title: string };
    client: string | null;
    revision_no: number | null;
    sent_at: string | null;
    expires_at: string | null;
    updated_at: string | null;
    action?: string;
};

export type ScopeItem = {
    id?: number;
    type: string;
    type_label?: string;
    title: string;
    detail?: string | null;
};

export type Baseline = {
    id: number;
    version: number;
    title: string;
    summary: string | null;
    locked: boolean;
    locked_at: string | null;
    content_hash: string | null;
    items: ScopeItem[];
};

export type Revision = {
    id: number;
    revision_no: number;
    title: string;
    description: string;
    scope_reason: string | null;
    scope_excerpt: string | null;
    price: Money;
    currency: string;
    timeline: {
        type: string;
        value?: string | number | null;
        note?: string | null;
    };
    timeline_label: string;
    payment_rule: string;
    payment_rule_label: string;
    payment_url: string | null;
    payment_instructions: string | null;
    terms_note: string | null;
    locked_at: string | null;
    snapshot_hash: string | null;
    scope_items: {
        id: number;
        type: string;
        type_label: string;
        title: string;
    }[];
    decision: {
        decision: 'approved' | 'declined';
        client_name: string;
        client_email: string;
        reason: string | null;
        decided_at: string;
        decided_at_utc: string;
    } | null;
};

export type ProjectSummary = {
    id: number;
    title: string;
    code: string | null;
    status: string;
    status_label: string;
    currency: string;
    base_amount: Money | null;
    revision_allowance: number | null;
    starts_on: string | null;
    ends_on: string | null;
    client: { id: number; name: string } | null;
    change_requests_count?: number;
};

export type ClientSummary = {
    id: number;
    type: 'company' | 'person';
    name: string;
    company: string | null;
    display_name: string;
    email: string | null;
    phone: string | null;
    notes: string | null;
    archived: boolean;
    projects_count?: number;
};

export type Option = { id: number; name: string };
