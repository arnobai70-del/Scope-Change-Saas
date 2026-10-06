import type { Money } from '@/types';

export function moneyList(
    values: Money[] | null | undefined,
    empty = '0',
): string {
    if (!values || values.length === 0) {
        return empty;
    }

    return values.map((value) => value.formatted).join(' · ');
}

export function plural(count: number, word: string): string {
    return `${count} ${word}${count === 1 ? '' : 's'}`;
}
