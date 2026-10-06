/**
 * Minimal JSON POST helper that sends Laravel's XSRF cookie, for the few
 * endpoints that return JSON instead of an Inertia response.
 */
export async function postJson<T>(
    url: string,
    body: Record<string, unknown>,
): Promise<T> {
    const xsrf = document.cookie
        .split('; ')
        .find((row) => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(xsrf ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrf) } : {}),
        },
        body: JSON.stringify(body),
    });

    const data = (await response.json().catch(() => ({}))) as T & {
        message?: string;
        errors?: Record<string, string[]>;
    };

    if (!response.ok) {
        const first = data.errors
            ? Object.values(data.errors)[0]?.[0]
            : undefined;
        throw new Error(
            first ?? data.message ?? 'Something went wrong. Please try again.',
        );
    }

    return data;
}
