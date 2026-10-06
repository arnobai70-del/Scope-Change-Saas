<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, Trash2 } from '@lucide/vue';
import Field from '@/components/app/Field.vue';
import NativeSelect from '@/components/app/NativeSelect.vue';
import TextArea from '@/components/app/TextArea.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Baseline } from '@/types';

const props = defineProps<{
    projectId: number;
    baseline: Baseline | null;
    projectTitle: string;
}>();
const emit = defineEmits<{ saved: [] }>();

const types = [
    { value: 'deliverable', label: 'Deliverable' },
    { value: 'exclusion', label: 'Exclusion' },
    { value: 'assumption', label: 'Assumption / client responsibility' },
    { value: 'revision', label: 'Revision allowance' },
];

const form = useForm({
    title: props.baseline?.title ?? `${props.projectTitle} scope`,
    summary: props.baseline?.summary ?? '',
    items: props.baseline?.items.length
        ? props.baseline.items.map((item) => ({
              type: item.type,
              title: item.title,
              detail: item.detail ?? '',
          }))
        : [
              { type: 'deliverable', title: '', detail: '' },
              { type: 'exclusion', title: '', detail: '' },
          ],
});

function add(type = 'deliverable'): void {
    form.items.push({ type, title: '', detail: '' });
}

function move(index: number, delta: number): void {
    const target = index + delta;

    if (target < 0 || target >= form.items.length) {
        return;
    }

    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);
}

function itemError(index: number, field: string): string | undefined {
    return (form.errors as Record<string, string>)[`items.${index}.${field}`];
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        items: data.items.filter((item) => item.title.trim() !== ''),
    })).put(`/app/projects/${props.projectId}/scope`, {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
}
</script>

<template>
    <form class="grid gap-5" @submit.prevent="submit">
        <p
            v-if="baseline?.locked"
            class="rounded-md bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950 dark:text-amber-100"
        >
            Version {{ baseline.version }} is locked. Saving creates version
            {{ baseline.version + 1 }}; the locked version stays unchanged.
        </p>
        <Field label="Scope title" for="scope-title" :error="form.errors.title">
            <Input
                id="scope-title"
                v-model="form.title"
                required
                maxlength="160"
            />
        </Field>
        <Field label="Summary" for="scope-summary" :error="form.errors.summary">
            <TextArea
                id="scope-summary"
                v-model="form.summary"
                :rows="2"
                placeholder="One or two sentences describing the engagement"
            />
        </Field>

        <div class="space-y-3">
            <p class="text-sm font-medium">Scope items</p>
            <InputError :message="form.errors.items" />
            <div
                v-for="(item, index) in form.items"
                :key="index"
                class="grid gap-2 rounded-lg border p-3 sm:grid-cols-[12rem_1fr_auto]"
            >
                <NativeSelect
                    v-model="item.type"
                    :aria-label="`Item ${index + 1} type`"
                >
                    <option
                        v-for="type in types"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option>
                </NativeSelect>
                <div class="grid gap-2">
                    <Input
                        v-model="item.title"
                        :aria-label="`Item ${index + 1} title`"
                        placeholder="e.g. 5-page marketing website"
                        maxlength="255"
                    />
                    <InputError :message="itemError(index, 'title')" />
                    <Input
                        v-model="item.detail"
                        :aria-label="`Item ${index + 1} detail`"
                        placeholder="Detail (optional)"
                        maxlength="2000"
                    />
                </div>
                <div class="flex gap-1 sm:flex-col">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Move up"
                        @click="move(index, -1)"
                        ><ArrowUp class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Move down"
                        @click="move(index, 1)"
                        ><ArrowDown class="size-4"
                    /></Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        aria-label="Remove item"
                        @click="form.items.splice(index, 1)"
                        ><Trash2 class="size-4"
                    /></Button>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="add('deliverable')"
                    ><Plus class="size-4" /> Deliverable</Button
                >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="add('exclusion')"
                    ><Plus class="size-4" /> Exclusion</Button
                >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="add('assumption')"
                    ><Plus class="size-4" /> Assumption</Button
                >
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="add('revision')"
                    ><Plus class="size-4" /> Revision allowance</Button
                >
            </div>
        </div>

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing"
                >Save scope</Button
            >
        </div>
    </form>
</template>
