<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/app/PageHeader.vue';
import Panel from '@/components/app/Panel.vue';
import { moneyList } from '@/lib/format';
import type { Money } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Reports', href: '/app/reports' }] },
});

const props = defineProps<{
    report: {
        series: {
            month: string;
            approved: number;
            declined: number;
            value: number;
        }[];
        currency: string;
        approval_rate: number | null;
        median_response_hours: number | null;
        revenue_protected: Money[];
        counts: Record<string, number>;
    };
    fullAnalytics: boolean;
}>();

const max = computed(() =>
    Math.max(1, ...props.report.series.map((row) => row.value)),
);
const formatter = computed(() => {
    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: props.report.currency,
        });
    } catch {
        return new Intl.NumberFormat();
    }
});
const digits = computed(
    () => formatter.value.resolvedOptions().maximumFractionDigits ?? 2,
);

function format(minor: number): string {
    return formatter.value.format(minor / 10 ** digits.value);
}
</script>

<template>
    <Head title="Reports" />
    <div class="flex flex-1 flex-col gap-6 p-4">
        <PageHeader
            title="Reports"
            description="How much extra work you turned into approved revenue."
        />

        <div
            v-if="!fullAnalytics"
            class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-100"
        >
            Showing the last 3 months.
            <Link href="/app/billing" class="font-medium underline"
                >Upgrade to Pro</Link
            >
            for 12-month history and full analytics.
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Revenue protected</p>
                <p class="mt-1 text-xl font-semibold">
                    {{ moneyList(report.revenue_protected) }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    Approval rate (12 months)
                </p>
                <p class="mt-1 text-xl font-semibold">
                    {{
                        report.approval_rate === null
                            ? '—'
                            : `${report.approval_rate}%`
                    }}
                </p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">
                    Median time to decision
                </p>
                <p class="mt-1 text-xl font-semibold">
                    {{
                        report.median_response_hours === null
                            ? '—'
                            : `${report.median_response_hours} h`
                    }}
                </p>
            </div>
        </div>

        <Panel
            :title="`Approved value by month (${report.currency})`"
            description="Other currencies are listed in Revenue protected."
        >
            <div
                class="flex h-48 items-end gap-2"
                role="img"
                :aria-label="`Approved value by month in ${report.currency}`"
            >
                <div
                    v-for="row in report.series"
                    :key="row.month"
                    class="flex flex-1 flex-col items-center gap-1"
                >
                    <div
                        class="w-full rounded-t bg-primary/80"
                        :style="{
                            height: `${(row.value / max) * 100}%`,
                            minHeight: row.value > 0 ? '4px' : '0',
                        }"
                        :title="format(row.value)"
                    />
                </div>
            </div>
            <div
                class="mt-2 flex gap-2 text-center text-xs text-muted-foreground"
            >
                <div
                    v-for="row in report.series"
                    :key="row.month"
                    class="flex-1 truncate"
                >
                    {{ row.month }}
                </div>
            </div>
            <table class="mt-6 w-full text-sm">
                <thead class="text-left text-muted-foreground">
                    <tr>
                        <th class="py-1 font-medium">Month</th>
                        <th class="py-1 font-medium">Approved</th>
                        <th class="py-1 font-medium">Declined</th>
                        <th class="py-1 text-right font-medium">Value</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="row in report.series" :key="row.month">
                        <td class="py-1.5">{{ row.month }}</td>
                        <td>{{ row.approved }}</td>
                        <td>{{ row.declined }}</td>
                        <td class="text-right">{{ format(row.value) }}</td>
                    </tr>
                </tbody>
            </table>
        </Panel>

        <Panel title="Requests by status">
            <dl class="grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                <div
                    v-for="(count, status) in report.counts"
                    :key="status"
                    class="rounded-lg bg-muted/50 p-3"
                >
                    <dt class="text-muted-foreground capitalize">
                        {{ String(status).replace(/_/g, ' ') }}
                    </dt>
                    <dd class="text-lg font-semibold">{{ count }}</dd>
                </div>
            </dl>
        </Panel>
    </div>
</template>
