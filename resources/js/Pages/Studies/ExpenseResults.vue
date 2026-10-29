<!--
  CFO Hotels — Expense results ( /studies/{id}/expenses-results )
  Location: resources/js/Pages/Studies/ExpenseResults.vue
  Read only. The expenses of each calendar year, worked out from the inputs of the expense pages: direct expenses of each department, the undistributed
  operating expenses, the base management fee and the start-up cost and pre-operating expenses, with the total of each group.
  Server side: App\Http\Controllers\Hospitality\ExpenseResultsController (figures: App\Services\Expenses\ExpenseResultsService).
-->
<script setup>
import { computed, ref } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ResultsHead from '@/Components/Reports/ResultsHead.vue';
import ResultsTable from '@/Components/Reports/ResultsTable.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { figure, sparkPoints } from '@/Utils/statements';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    years: { type: Array, default: () => [] },
    calculated: { type: Boolean, default: false },
    groups: { type: Array, default: () => [] },
    totals: { type: Object, default: () => ({}) },
});

const { t } = useI18n();
const heat = ref(true);
const dense = ref(false);
const sum = (map) => Object.values(map ?? {}).reduce((a, b) => a + (Number(b) || 0), 0);
const hasAny = computed(() => props.groups.some((g) => sum(g.total) > 0));
// a department with nothing in it is left out of the table
const shown = computed(() => props.groups.map((g) => ({
    ...g,
    sections: g.sections
        .filter((s) => sum(s.total) > 0 || g.key === 'management' || g.key === 'startup')
        .map((s) => ({ ...s, lines: g.key === 'management' || g.key === 'startup' ? s.lines : s.lines.filter((l) => sum(l.values) > 0) })),
})));
const label = (group, section) => (group.key === 'direct' || group.key === 'undistributed' ? t(`expenses.menu.${section.key}`) : t(`expenses.outcome.groups.${group.key}`));
const lineLabel = (line) => t(`expenses.outcome.lines.${line.key}`);

// each group is one table: a heading for each department, its lines, then the total of the group
const COLORS = { direct: 'blue', undistributed: 'amber', management: 'cyan', startup: 'green' };
const tables = computed(() => shown.value.map((g) => {
    const headed = g.key === 'direct' || g.key === 'undistributed';

    return {
        key: g.key,
        sections: g.sections.map((s) => ({
            key: s.key,
            head: headed ? label(g, s) : '',
            rows: s.lines.map((l) => ({ key: l.key, label: lineLabel(l), values: l.values, color: COLORS[g.key], attrs: { 'data-line': l.key } })),
        })),
        foot: g.sections.length > 1 || headed ? [{ key: 'total', label: t('expenses.outcome.total'), values: g.total ?? {}, attrs: { 'data-group-total': '' } }] : [],
    };
}));
const operatingFoot = computed(() => [{ key: 'operating', label: t('expenses.outcome.operating'), values: props.totals.operating ?? {}, attrs: {} }]);

const figures = computed(() => {
    const totals = props.years.map((y) => Number(props.totals.operating?.[y.index]) || 0);
    if (!totals.length) return [];
    const best = totals.indexOf(Math.max(...totals));
    const all = totals.reduce((a, b) => a + b, 0);
    const startup = props.groups.find((g) => g.key === 'startup');

    return [
        { key: 'total', label: t('expenses.outcome.operating'), text: figure(all), spark: sparkPoints(totals) },
        { key: 'peak', label: t('rs.kpi.peak'), text: `${props.years[best].year} · ${figure(totals[best])}` },
        { key: 'average', label: t('rs.kpi.average'), text: figure(all / totals.length) },
        { key: 'startup', label: t('expenses.outcome.groups.startup'), text: figure(sum(startup?.total)) },
    ];
});
</script>

<template>
    <AppLayout :title="t('expenses.outcome.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('expenses.outcome.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="expenses.results" />

        <div v-if="!calculated" class="note bad" role="alert" data-not-calculated>
            <AppIcon name="alert" :size="14" /> {{ t('expenses.outcome.notCalculated') }}
        </div>
        <div v-else-if="!hasAny" class="note" role="status" data-no-expenses>
            <AppIcon name="info" :size="14" /> {{ t('expenses.outcome.none') }}
        </div>

        <template v-if="calculated">
            <ResultsHead v-model:heat="heat" v-model:dense="dense" family="expenses" icon="table" :title="t('expenses.outcome.title')" :figures="hasAny ? figures : []" />

            <section v-for="g in tables" :key="g.key" class="rv-results" :aria-label="t(`expenses.outcome.groups.${g.key}`)">
                <p v-if="g.key === 'direct'" class="mute">{{ t('expenses.outcome.text') }}</p>
                <ResultsTable
                    :data-group="g.key" data-expense-table :title="t(`expenses.outcome.groups.${g.key}`)" :years="years" :total-label="t('expenses.outcome.total')"
                    :sections="g.sections" :foot="g.foot" :heat="heat" :dense="dense"
                />
            </section>

            <section class="rv-results" :aria-label="t('expenses.outcome.operating')">
                <p class="mute">{{ t('expenses.outcome.operatingNote') }}</p>
                <ResultsTable
                    data-operating-total :title="t('expenses.outcome.operating')" :years="years" :total-label="t('expenses.outcome.total')"
                    :sections="[]" :foot="operatingFoot" :heat="heat" :dense="dense"
                />
            </section>
        </template>
    </AppLayout>
</template>
