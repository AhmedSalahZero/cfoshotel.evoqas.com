<!--
  CFO Hotels — Statements ( /studies/{id}/statements/{family}/{type} )
  Location: resources/js/Pages/Studies/Statement.vue
  Read only. One screen for the 16 statements of the previous application: receivables (collection), inventory, disposables payments,
  fixed expenses (prepaid), management fees, taxes and insurance, fixed assets, suppliers, construction payable and the loan schedules.
  Server side: App\Http\Controllers\Hospitality\StatementsController (figures: App\Services\Statements\StatementService).
  The table itself is Components/Statements/StatementTable.vue.
-->
<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import StatementTable from '@/Components/Statements/StatementTable.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    family: { type: String, required: true },
    type: { type: String, required: true },
    types: { type: Array, default: () => [] },
    links: { type: Object, default: () => ({}) },
    calculated: { type: Boolean, default: false },
    kind: { type: String, default: 'periods' },
    tabs: { type: Array, default: () => [] },
    intervals: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
    totals: { type: Object, default: null },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
const ACCENTS = { collection: 'green', inventory: 'orange', disposables: 'violet', prepaid: 'cyan', fees: 'violet', taxes: 'blue', assets: 'blue', loans: 'green' };
const accent = computed(() => ACCENTS[props.family] ?? 'blue');

const title = computed(() => tr(`stmt.titles.${props.family}.${props.type}`, tr(`stmt.menu.${props.family}`, props.family)));
const typeLabel = (type) => tr(`stmt.types.${props.family}.${type}`, type);

// the interval chosen by the reader (monthly first, as in the previous application)
const interval = ref(props.tabs[0]?.name ?? 'monthly');
watch(() => props.tabs, (tabs) => { if (!tabs.some((x) => x.name === interval.value)) interval.value = tabs[0]?.name ?? 'monthly'; });
const current = computed(() => props.intervals.find((x) => x.name === interval.value) ?? props.intervals[0] ?? null);

const heat = ref(true);
const dense = ref(false);
const hasTables = computed(() => !!current.value && current.value.tables.length > 0);
const cols = ['no', 'date', 'beginning', 'schedule', 'interest', 'principle', 'end'];
const kpis = computed(() => (props.totals ? ['schedule', 'interest', 'principle'].map((k) => ({ key: k, text: props.totals[k] })) : []));
</script>

<template>
    <AppLayout :title="title" active="studies" :accent="accent">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('stmt.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" :current="`statements.${family}`" />

        <header class="st-band" :data-family="family">
            <div class="st-band-title">
                <span class="st-band-icon" aria-hidden="true"><AppIcon name="table" :size="20" /></span>
                <div>
                    <small>{{ tr(`stmt.menu.${family}`, family) }}</small>
                    <h3>{{ title }}</h3>
                </div>
            </div>
            <nav v-if="types.length > 1" class="st-types" :aria-label="t('stmt.typesLabel')">
                <Link v-for="each in types" :key="each" :href="links[each]" class="st-type" :class="{ on: each === type }" :aria-current="each === type ? 'page' : undefined">{{ typeLabel(each) }}</Link>
            </nav>
        </header>

        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('stmt.notCalculated') }}
        </div>

        <template v-else-if="kind === 'schedule'">
            <div v-if="!rows.length" class="note" role="status" data-empty><AppIcon name="info" :size="14" /> {{ t('stmt.empty') }}</div>
            <template v-else>
                <ul class="st-kpis" data-totals>
                    <li v-for="k in kpis" :key="k.key" :data-kpi="k.key"><small>{{ t(`stmt.loan.total.${k.key}`) }}</small><strong dir="ltr">{{ k.text }}</strong></li>
                </ul>
                <section class="st-card" data-schedule>
                    <header class="st-head"><h3><span class="st-dot" aria-hidden="true" />{{ title }}</h3></header>
                    <div class="st-scroll" role="region" tabindex="0" :aria-label="title">
                        <table class="st-table st-loan">
                            <thead>
                                <tr class="st-months">
                                    <th v-for="c in cols" :key="c" scope="col" :class="{ 'st-name': c === 'no' }">{{ t(`stmt.loan.${c}`) }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in rows" :key="r.no" :data-row="r.no">
                                    <th scope="row" class="st-name"><span class="st-bar" aria-hidden="true" /><span class="st-label">{{ r.no }}</span></th>
                                    <td v-for="c in cols.slice(1)" :key="c" dir="ltr" :class="{ 'st-end': c === 'end', 'st-odd': r.no % 2 === 0 }">{{ r[c] }}</td>
                                </tr>
                            </tbody>
                            <tfoot v-if="totals">
                                <tr class="st-foot">
                                    <th scope="row" class="st-name" colspan="3">{{ t('stmt.loan.totals') }}</th>
                                    <td dir="ltr">{{ totals.schedule }}</td>
                                    <td dir="ltr">{{ totals.interest }}</td>
                                    <td dir="ltr">{{ totals.principle }}</td>
                                    <td />
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </section>
            </template>
        </template>

        <template v-else>
            <div class="st-bar-tools">
                <div class="seg" role="tablist" :aria-label="t('stmt.intervalLabel')">
                    <button v-for="tab in tabs" :key="tab.name" type="button" role="tab" class="btn" :class="{ 'btn-line': tab.name !== interval }" :aria-selected="tab.name === interval" :data-interval="tab.name" @click="interval = tab.name">{{ t(`stmt.intervals.${tab.name}`) }}</button>
                </div>
                <label class="st-toggle"><input v-model="heat" type="checkbox"><AppIcon name="heat" :size="14" />{{ t('stmt.heat') }}</label>
                <label class="st-toggle"><input v-model="dense" type="checkbox"><AppIcon name="rows" :size="14" />{{ t('stmt.dense') }}</label>
            </div>
            <div v-if="!hasTables" class="note" role="status" data-empty><AppIcon name="info" :size="14" /> {{ t('stmt.empty') }}</div>
            <StatementTable
                v-for="table in current?.tables ?? []" :key="`${interval}-${table.key}`"
                :title="tr(`stmt.tables.${table.title}`, table.title)" :columns="current.columns" :rows="table.rows" :heat="heat" :dense="dense"
            />
        </template>
    </AppLayout>
</template>
