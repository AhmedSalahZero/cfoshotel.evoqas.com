<!--
  CFO Hotels — ManpowerTables (positions with a salary, and how many people hold each position in every month)
  Location: resources/js/Components/Expenses/ManpowerTables.vue
  The first table: a line for each position (name, net salary, currency, escalation up to the opening, the net salary at the opening, the annual escalation, taxes
  and social insurance). The second table appears for the positions that have a name: one box for each month of operation (the first 48 months; the last
  month is kept after that). The three dots copy a number into the months after it.
-->
<script setup>
import { computed } from 'vue';
import CopyInput from '@/Components/CopyInput.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { adrAtOperation, money } from '@/Utils/adr';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    rows: { type: Array, required: true },
    dates: { type: Array, required: true },
    currencies: { type: Array, required: true },
    estimationDate: { type: String, default: '' },
    daysDifference: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    prefix: { type: String, required: true },
});

const { t } = useI18n();
const currencyOptions = props.currencies.map((c) => ({ value: c.id, text: c.name }));
const bad = (i, field) => !!props.errors[`${props.prefix}.${i}.${field}`];
const named = computed(() => props.rows.map((row, i) => ({ row, i })).filter(({ row }) => String(row.name ?? '').trim() !== ''));

function copyDown(field) {
    const first = props.rows[0];
    props.rows.forEach((row, i) => { if (i > 0) row[field] = first[field]; });
}

function copyRight(row, from) {
    const start = props.dates.findIndex((d) => d.index === from.index);
    for (let i = start + 1; i < props.dates.length; i += 1) row.heads[props.dates[i].index] = row.heads[from.index];
}
</script>

<template>
    <div>
        <div class="rv-scroll" role="region" :aria-label="t('expenses.sections.rooms_manpower_expense')" tabindex="0">
            <table class="ex-grid ex-manpower" data-manpower-table>
                <thead>
                    <tr>
                        <th scope="col" class="rv-rowhead">{{ t('expenses.manpower.position') }}</th>
                        <th scope="col">{{ t('expenses.manpower.salary') }}</th>
                        <th scope="col">{{ t('expenses.manpower.currency') }}</th>
                        <th scope="col">{{ t('expenses.manpower.estimation') }}</th>
                        <th scope="col">{{ t('expenses.manpower.escalation') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.manpower.escalation')}`" @click="copyDown('escalation')">⋯</button></th>
                        <th scope="col">{{ t('expenses.manpower.atOperation') }}</th>
                        <th scope="col">{{ t('expenses.manpower.annual') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.manpower.annual')}`" @click="copyDown('annual')">⋯</button></th>
                        <th scope="col">{{ t('expenses.manpower.taxes') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.manpower.taxes')}`" @click="copyDown('taxes')">⋯</button></th>
                        <th scope="col">{{ t('expenses.manpower.insurance') }}<button v-if="!disabled" type="button" class="ex-col" :title="t('expenses.copyColumn')" :aria-label="`${t('expenses.copyColumn')}: ${t('expenses.manpower.insurance')}`" @click="copyDown('insurance')">⋯</button></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, i) in rows" :key="i" data-position-row>
                        <th scope="row" class="rv-rowhead ex-name">
                            <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('expenses.manpower.namePlaceholder')" :aria-label="t('expenses.manpower.position')" :disabled="disabled">
                        </th>
                        <td :class="{ bad: bad(i, 'salary') }"><NumberField v-model="row.salary" :min="0" :max="1000000000" :invalid="bad(i, 'salary')" :disabled="disabled" /></td>
                        <td><SelectField v-model="row.currency" :options="currencyOptions" :disabled="disabled" /></td>
                        <td class="rv-num" dir="ltr">{{ estimationDate }}</td>
                        <td :class="{ bad: bad(i, 'escalation') }"><NumberField v-model="row.escalation" unit="%" :min="0" :max="100" :invalid="bad(i, 'escalation')" :disabled="disabled" /></td>
                        <td class="rv-num" dir="ltr" data-at-operation>{{ money(adrAtOperation(row.salary, row.escalation, daysDifference), 0) }}</td>
                        <td :class="{ bad: bad(i, 'annual') }"><NumberField v-model="row.annual" unit="%" :min="0" :max="100" :invalid="bad(i, 'annual')" :disabled="disabled" /></td>
                        <td :class="{ bad: bad(i, 'taxes') }"><NumberField v-model="row.taxes" unit="%" :min="0" :max="99.99" :invalid="bad(i, 'taxes')" :disabled="disabled" /></td>
                        <td :class="{ bad: bad(i, 'insurance') }"><NumberField v-model="row.insurance" unit="%" :min="0" :max="99.99" :invalid="bad(i, 'insurance')" :disabled="disabled" /></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <h4 class="ex-sub">{{ t('expenses.manpower.countTitle') }}</h4>
        <p class="mute rv-cap">{{ t('expenses.manpower.countText') }}</p>
        <p v-if="!named.length" class="mute" data-no-positions>{{ t('expenses.manpower.countNone') }}</p>
        <div v-else class="rv-scroll" role="region" :aria-label="t('expenses.manpower.countTitle')" tabindex="0">
            <table class="ex-grid ex-heads" data-heads-table>
                <thead>
                    <tr>
                        <th scope="col" class="rv-rowhead">{{ t('expenses.manpower.position') }}</th>
                        <th v-for="d in dates" :key="d.index" scope="col"><span dir="ltr">{{ d.label }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="{ row, i } in named" :key="i" data-heads-row>
                        <th scope="row" class="rv-rowhead">{{ row.name }}</th>
                        <td v-for="(d, k) in dates" :key="d.index" :class="{ bad: bad(i, `heads.${d.index}`) }">
                            <CopyInput v-model="row.heads[d.index]" :copy="!disabled && k < dates.length - 1" :invalid="bad(i, `heads.${d.index}`)" :aria-label="`${row.name} ${d.label}`" :disabled="disabled" @copy="copyRight(row, d)" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="dates.length >= 48" class="mute rv-cap">{{ t('expenses.manpower.moreMonths') }}</p>
    </div>
</template>
