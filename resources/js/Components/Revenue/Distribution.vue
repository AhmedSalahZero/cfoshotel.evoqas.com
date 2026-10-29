<!--
  CFO Hotels — Distribution (how a year's demand is spread over its months: a seasonality plan)
  Location: resources/js/Components/Revenue/Distribution.vue
  <Distribution :plan="form.guestPlan" :facilities="[{ key: 1, name: 'Ballroom' }]" error-prefix="guestPlan" :errors="form.errors" :allow-per-facility="true" />
  A plan is general (one for all facilities) or per facility; flat (equal months), quarterly (four shares) or monthly (twelve shares).
  The shares of a quarterly or monthly plan must add up to 100 %; the total is shown under each row while typing.
-->
<script setup>
import { computed } from 'vue';
import CopyInput from '@/Components/CopyInput.vue';
import ChoiceCards from '@/Components/Form/ChoiceCards.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    plan: { type: Object, required: true },
    facilities: { type: Array, required: true },
    allowPerFacility: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, required: true },
    legend: { type: String, default: '' },
});

const { t, tr } = useI18n();
const months = Array.from({ length: 12 }, (_, i) => String(i + 1).padStart(2, '0'));
const quarters = ['quarter-one', 'quarter-two', 'quarter-three', 'quarter-four'];

const typeOptions = computed(() => ['general', 'per_facility']
    .filter((v) => v === 'general' || props.allowPerFacility)
    .map((v) => ({ value: v, title: t(`revenue.plan.${v}`), text: t(`revenue.plan.${v}Text`) })));
const intervalOptions = computed(() => ['flat', 'quarterly', 'monthly'].map((v) => ({ value: v, title: t(`revenue.plan.${v}`), text: t(`revenue.plan.${v}Text`) })));

const keys = computed(() => (props.plan.interval === 'quarterly' ? quarters : months));
const headers = computed(() => (props.plan.interval === 'quarterly' ? quarters.map((q) => t(`revenue.plan.quarters.${q}`)) : tr('studies.monthsShort')));
const rows = computed(() => (props.plan.type === 'per_facility'
    ? props.facilities.map((f) => ({ key: f.key, label: f.name, cells: (props.plan.interval === 'quarterly' ? props.plan.perQuarters : props.plan.perMonths)[f.key] }))
    : [{ key: 'all', label: t('revenue.plan.allFacilities'), cells: props.plan.interval === 'quarterly' ? props.plan.quarters : props.plan.months }]));
const flat = computed(() => props.plan.interval === 'flat');
const total = (row) => keys.value.reduce((sum, k) => sum + (Number(row.cells?.[k]) || 0), 0);
const balanced = (row) => Math.abs(total(row) - 100) < 0.01;
const shown = (n) => Math.round(n * 100) / 100;

function copyRight(row, from) {
    for (let i = keys.value.indexOf(from) + 1; i < keys.value.length; i += 1) row.cells[keys.value[i]] = row.cells[from];
}
</script>

<template>
    <div class="rv-plan">
        <ChoiceCards v-if="typeOptions.length > 1" v-model="plan.type" :options="typeOptions" :legend="legend" :disabled="disabled" />
        <ChoiceCards v-model="plan.interval" :options="intervalOptions" :legend="legend" :disabled="disabled" />
        <div class="rv-scroll" role="region" tabindex="0" :aria-label="legend || undefined">
            <table class="rv-grid" :class="{ 'rv-months': plan.interval !== 'quarterly' }">
                <thead>
                    <tr>
                        <th scope="col" class="rv-rowhead" />
                        <th v-for="(h, i) in headers" :key="i" scope="col"><span class="rv-year">{{ h }}</span></th>
                        <th v-if="!flat" scope="col">{{ t('revenue.plan.total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in rows" :key="row.key" data-plan-row>
                        <th scope="row" class="rv-rowhead">{{ row.label }}</th>
                        <td v-for="(k, i) in keys" :key="k">
                            <span v-if="flat" class="rv-fixed" dir="ltr">{{ plan.interval === 'flat' ? shown(100 / 12) : '' }}%</span>
                            <CopyInput v-else-if="row.cells" v-model="row.cells[k]" suffix="%" :max="100" :copy="!disabled && i < keys.length - 1" :aria-label="`${row.label} ${headers[i]}`" :disabled="disabled" @copy="copyRight(row, k)" />
                        </td>
                        <td v-if="!flat" :class="balanced(row) ? 'ok' : 'bad'" class="rv-plan-total" data-plan-total><b dir="ltr">{{ shown(total(row)) }}%</b></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="!flat" class="mute rv-cap">{{ t('revenue.plan.mustBe100') }}</p>
    </div>
</template>
