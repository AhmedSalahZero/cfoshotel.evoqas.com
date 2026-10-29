<!--
  CFO Hotels — Financial statements ( /studies/{id}/reports/{income|cash|balance} )
  Location: resources/js/Pages/Studies/Report.vue
  Read only. The income statement, the cash in / out flow and the balance sheet of the previous application, each in the four periods
  (monthly, quarterly, semi-annually, annually). Lines open into the lines they are made of.
  Server side: App\Http\Controllers\Hospitality\ReportsController (figures: App\Services\Reports\ReportService).
  The table itself is Components/Reports/ReportTree.vue. The ratios have their own screen (Ratios.vue).
-->
<script setup>
import { computed, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import ReportTree from '@/Components/Reports/ReportTree.vue';
import StudyNotices from '@/Components/Studies/StudyNotices.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { figure, sparkPoints } from '@/Utils/statements';
import { summaryOf } from '@/Utils/reports';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    notices: { type: Object, default: () => ({}) },
    report: { type: String, required: true },
    links: { type: Object, default: () => ({}) },
    currency: { type: String, default: '' },
    calculated: { type: Boolean, default: false },
    kind: { type: String, default: 'tree' },
    tabs: { type: Array, default: () => [] },
    intervals: { type: Array, default: () => [] },
});

const { t } = useI18n();
const tr = (key, fallback) => {
    const value = t(key);

    return value === key ? fallback : value;
};
const ACCENTS = { income: 'green', cash: 'cyan', balance: 'blue' };
const accent = computed(() => ACCENTS[props.report] ?? 'blue');
const title = computed(() => t(`fin.titles.${props.report}`));

// the four periods, monthly first (as in the previous application)
const interval = ref(props.tabs[0]?.name ?? 'monthly');
watch(() => props.tabs, (tabs) => { if (!tabs.some((x) => x.name === interval.value)) interval.value = tabs[0]?.name ?? 'monthly'; });
const current = computed(() => props.intervals.find((x) => x.name === interval.value) ?? props.intervals[0] ?? null);

const heat = ref(true);
const dense = ref(false);
const open = ref(new Set());

// the figures to see first, from the lines of the period on show
const KEY_FIGURES = {
    income: [
        ['revenues', 'hotelRevenue/Total Revenues'], ['grossProfit', 'DepartmentsGrossProfit/Departments Gross Profit'],
        ['ebitda', 'EBITDA/Earnings Before Interest Taxes Depreciation & Amortization [ EBITDA ]'], ['netProfit', 'net_profit/Net Profit'],
    ],
    cash: [['cashIn', 'in/Total Cash In'], ['cashOut', 'out/Total Cash Out Report'], ['netCash', 'netCash/Net Cash Report'], ['accumulated', 'netCash/Accumulated Net Cash']],
    balance: [['assets', 'Total Assets/Total Assets'], ['liabilities', 'Total Current Liabilities/Total Current Liabilities'], ['investment', 'Total Investment/Total Investment'], ['equity', 'Owners Equity/Owners Equity']],
};
const figures = computed(() => (KEY_FIGURES[props.report] ?? []).map(([key, line]) => {
    const row = current.value?.rows.find((x) => x.key === line);
    if (!row) return null;
    const sum = summaryOf(props.report, row);

    return { key, text: figure(sum.value), negative: sum.value < 0, kind: sum.kind, spark: sparkPoints(row.values) };
}).filter(Boolean));
const check = computed(() => {
    if (props.report !== 'balance') return null;
    const row = current.value?.rows.find((x) => x.key === 'Check Error/Check Error');
    if (!row) return null;
    const worst = Math.max(0, ...(row.values ?? []).map((v) => Math.abs(Number(v) || 0)));

    return { ok: worst < 1, text: figure(worst) };
});
const hasRows = computed(() => !!current.value && current.value.rows.length > 0);
</script>

<template>
    <AppLayout :title="title" active="studies" :accent="accent">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t(`fin.lead.${report}`) }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" :current="`financial.${report}`" />

        <header class="st-band" :data-family="report">
            <div class="st-band-title">
                <span class="st-band-icon" aria-hidden="true"><AppIcon name="table" :size="20" /></span>
                <div>
                    <small>{{ t('expenses.groups.financial') }}</small>
                    <h3>{{ title }}</h3>
                </div>
            </div>
        </header>

        <StudyNotices v-if="calculated" :notices="notices" />
        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('fin.notCalculated') }}
        </div>

        <template v-else>
            <ul v-if="figures.length || check" class="st-kpis rp-kpis" data-key-figures :aria-label="t('fin.keyFigures')">
                <li v-for="f in figures" :key="f.key" :data-kpi="f.key">
                    <small>{{ t(`fin.kpi.${f.key}`) }}</small>
                    <strong dir="ltr" :class="{ 'rp-neg': f.negative }">{{ f.text }}</strong>
                    <svg v-if="f.spark" class="rp-kspark" viewBox="0 0 64 18" aria-hidden="true"><polyline :points="f.spark" /></svg>
                </li>
                <li v-if="check" class="rp-check" :class="check.ok ? 'ok' : 'bad'" data-check>
                    <small>{{ t('fin.kpi.check') }}</small>
                    <strong><AppIcon :name="check.ok ? 'check' : 'alert'" :size="16" /> {{ check.ok ? t('fin.kpi.balanced') : t('fin.kpi.unbalanced') }}</strong>
                </li>
            </ul>

            <div class="st-bar-tools">
                <div class="seg" role="tablist" :aria-label="t('fin.intervalLabel')">
                    <button v-for="tab in tabs" :key="tab.name" type="button" role="tab" class="btn" :class="{ 'btn-line': tab.name !== interval }" :aria-selected="tab.name === interval" :data-interval="tab.name" @click="interval = tab.name">{{ t(`stmt.intervals.${tab.name}`) }}</button>
                </div>
                <label class="st-toggle"><input v-model="heat" type="checkbox"><AppIcon name="heat" :size="14" />{{ t('fin.heat') }}</label>
                <label class="st-toggle"><input v-model="dense" type="checkbox"><AppIcon name="rows" :size="14" />{{ t('fin.dense') }}</label>
                <span v-if="currency" class="st-chip rp-currency">{{ t('fin.figuresIn', { currency }) }}</span>
            </div>

            <div v-if="!hasRows" class="note" role="status" data-empty><AppIcon name="info" :size="14" /> {{ t('fin.empty') }}</div>
            <ReportTree v-else v-model:open="open" :key="interval" :report="report" :title="`${title} — ${t(`stmt.intervals.${interval}`)}`" :columns="current.columns" :rows="current.rows" :heat="heat" :dense="dense" />
        </template>
    </AppLayout>
</template>
