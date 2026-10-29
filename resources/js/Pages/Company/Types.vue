<!--
  CFO Hotels — The company's lists of types ( /types ), company administrator only
  Location: resources/js/Pages/Company/Types.vue
  Five lists the study form and the revenue pages pick from: room types, food and beverage outlets, gaming areas, meeting halls, other revenue.
  Add, rename or remove a type. A type used by a study cannot be removed (it shows a lock). The "Total ..." row is managed by the system.
  Server side: App\Http\Controllers\Hospitality\TypeListsController.
-->
<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    lists: { type: Object, required: true },
    readOnly: { type: Boolean, default: false },
});

const { t } = useI18n();
const streams = ['rooms', 'foods', 'casinos', 'meetings', 'others'];
const icons = { rooms: 'bed', foods: 'utensils', casinos: 'dice', meetings: 'present', others: 'spark' };
const active = ref('rooms');
const drafts = reactive(Object.fromEntries(streams.map((s) => [s, (props.lists[s] ?? []).map((r) => ({ ...r }))])));
const form = useForm({ rows: [] });
const rows = computed(() => drafts[active.value]);
const locked = computed(() => props.readOnly);
const errorList = computed(() => [...new Set(Object.values(form.errors))]);

function add() {
    drafts[active.value].push({ id: null, title: '', inUse: false });
}
function remove(index) {
    drafts[active.value].splice(index, 1);
}
function save() {
    form.transform(() => ({ rows: drafts[active.value].map((r) => ({ id: r.id, title: r.title })) }))
        .put(route('types.update', active.value), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('revenue.types.pageTitle')" active="types" accent="cyan">
        <div class="pagehead">
            <div>
                <h2>{{ t('revenue.types.pageTitle') }}</h2>
                <p class="lead mb0">{{ t('revenue.types.lead') }}</p>
            </div>
        </div>
        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>

        <div class="rv-typetabs" role="tablist" :aria-label="t('revenue.types.pageTitle')">
            <button v-for="s in streams" :key="s" type="button" role="tab" :aria-selected="active === s" @click="active = s">
                <AppIcon :name="icons[s]" :size="15" />{{ t(`revenue.types.streams.${s}`) }}<span class="rv-count" dir="ltr">{{ drafts[s].length }}</span>
            </button>
        </div>

        <form class="rv-types" novalidate data-types-form role="tabpanel" @submit.prevent="save">
            <p class="mute">{{ t(`revenue.types.hint.${active}`) }}</p>
            <div v-if="errorList.length" class="note bad" role="alert"><ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul></div>
            <ul class="rv-typelist">
                <li v-for="(row, i) in rows" :key="row.id ?? `new-${i}`">
                    <input v-model="row.title" class="inp" type="text" maxlength="100" :aria-label="t('revenue.types.name')" :placeholder="t('revenue.types.namePlaceholder')" :disabled="locked">
                    <span v-if="row.inUse" class="rv-lock" :title="t('revenue.types.inUse')"><AppIcon name="lock" :size="13" />{{ t('revenue.types.inUseShort') }}</span>
                    <button v-else type="button" class="btn btn-line rv-x" :disabled="locked" :aria-label="t('revenue.types.remove', { name: row.title })" @click="remove(i)"><AppIcon name="trash" :size="14" /></button>
                </li>
                <li v-if="!rows.length" class="rv-empty">{{ t('revenue.types.empty') }}</li>
            </ul>
            <div class="rv-typebar">
                <button type="button" class="btn btn-line" :disabled="locked" @click="add"><AppIcon name="plus" :size="14" />{{ t('revenue.types.add') }}</button>
                <button type="submit" class="btn" :disabled="locked || form.processing"><AppIcon name="check" :size="15" />{{ t('revenue.types.save') }}</button>
            </div>
        </form>
    </AppLayout>
</template>
