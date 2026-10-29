<!--
  CFO Hotels — Furniture, Fixtures, and Equipment (FF&E) ( /studies/{id}/ffe-cost )
  Location: resources/js/Pages/Studies/FfeCost.vue
  The "Furniture, Fixtures, and Equipment (FF&E) Input Sheet" of the old application:
    1 FF&E items       ten lines: FFE Item Name, Choose Currency, Item Cost, Contingency Rate %, Total Cost (worked out), Depreciation Duration,
                       Replacement Cost Rate and Replacement Interval. A line is used when it has a name; clear the name to remove the line.
    2 Execution        Start Date, Duration (Months), End Date (start + duration, not after the opening), Select Execution Method
    3 Payment terms and funding, and the loan when part is funded by debt
  Server side: App\Http\Controllers\Hospitality\FfeCostController, rules in App\Http\Requests\Investment\FfeCostRequest.
-->
<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SectionCard from '@/Components/Form/SectionCard.vue';
import SectionNav from '@/Components/Form/SectionNav.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import FundingBlock from '@/Components/Investment/FundingBlock.vue';
import LoanForm from '@/Components/Investment/LoanForm.vue';
import PaymentTerms from '@/Components/Investment/PaymentTerms.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { debtRate, endIndex, sum, totalCost } from '@/Utils/investment';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    currencies: { type: Array, default: () => [] },
    months: { type: Array, default: () => [] },
    opening: { type: Number, default: 0 },
    endLabels: { type: Object, default: () => ({}) },
    lists: { type: Object, required: true },
    form: { type: Object, required: true },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const currencyOptions = computed(() => props.currencies.map((c) => ({ value: c.id, text: c.name })));
const monthOptions = computed(() => props.months.map((m) => ({ value: m.index, text: m.label })));
const years = (list) => list.map((y) => ({ value: y, text: t('investment.years', { n: y }) }));
const depreciationOptions = years(props.lists.depreciation);
const replacementOptions = years(props.lists.replacement);
const executionOptions = props.lists.execution.map((v) => ({ value: v, text: t(`investment.execution.${v}`) }));

const rowTotal = (row) => totalCost(row.cost, row.contingency);
const itemsTotal = computed(() => sum(form.rows.map(rowTotal)));
const debt = computed(() => debtRate(form.equity));
const end = computed(() => endIndex(form.dates.start, form.dates.duration));
const endText = computed(() => props.endLabels[end.value] ?? '');
const lateEnd = computed(() => end.value > props.opening);

const sections = computed(() => [
    { id: 'inv-items', number: 1, title: t('investment.ffe.sectionTitle'), text: t('investment.ffe.sectionLead') },
    { id: 'inv-execution', number: 2, title: t('investment.ffe.executionTitle'), text: t('investment.ffe.executionLead') },
    { id: 'inv-funding', number: 3, title: t('investment.ffe.fundingTitle'), text: t('investment.ffe.fundingLead') },
]);
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.title, state: '' })));

const error = (key) => form.errors[key] ?? '';
const bad = (i, field) => !!form.errors[`rows.${i}.${field}`];
const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.investment.ffe.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('investment.ffe.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('investment.ffe.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="investment.ffe" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-ffe-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-bind="sections[0]">
                    <div class="rv-scroll" role="region" :aria-label="sections[0].title" tabindex="0">
                        <table class="ex-grid inv-items" data-ffe-table>
                            <thead>
                                <tr>
                                    <th scope="col" class="rv-rowhead">{{ t('investment.fields.ffeItemName') }}</th>
                                    <th scope="col">{{ t('investment.fields.currency') }}</th>
                                    <th scope="col">{{ t('investment.fields.itemCost') }}</th>
                                    <th scope="col">{{ t('investment.fields.contingencyShort') }}</th>
                                    <th scope="col">{{ t('investment.fields.totalCost') }}</th>
                                    <th scope="col">{{ t('investment.fields.depreciationDuration') }}</th>
                                    <th scope="col">{{ t('investment.fields.replacementCostRate') }}</th>
                                    <th scope="col">{{ t('investment.fields.replacementInterval') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, i) in form.rows" :key="i" data-ffe-row>
                                    <th scope="row" class="rv-rowhead inv-name">
                                        <input v-model="row.name" class="inp" type="text" maxlength="120" :placeholder="t('investment.fields.ffeItemPlaceholder')" :aria-label="t('investment.fields.ffeItemName')" :disabled="locked">
                                    </th>
                                    <td><SelectField v-model="row.currency" :options="currencyOptions" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(i, 'cost') }"><NumberField v-model="row.cost" :min="0" :max="1000000000000" :invalid="bad(i, 'cost')" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(i, 'contingency') }"><NumberField v-model="row.contingency" unit="%" :min="0" :max="100" :invalid="bad(i, 'contingency')" :disabled="locked" /></td>
                                    <td class="rv-num" dir="ltr" data-row-total>{{ formatNumber(rowTotal(row)) }}</td>
                                    <td :class="{ bad: bad(i, 'depreciation') }"><SelectField v-model="row.depreciation" :options="depreciationOptions" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(i, 'replacementRate') }"><NumberField v-model="row.replacementRate" unit="%" :min="0" :max="100" :invalid="bad(i, 'replacementRate')" :disabled="locked" /></td>
                                    <td :class="{ bad: bad(i, 'replacementInterval') }"><SelectField v-model="row.replacementInterval" :options="replacementOptions" :disabled="locked" /></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="ex-total">
                                    <th scope="row" class="rv-rowhead" colspan="4">{{ t('investment.fields.total') }}</th>
                                    <td class="rv-num" dir="ltr" data-items-total>{{ formatNumber(itemsTotal) }}</td>
                                    <td colspan="3" />
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="mute rv-cap">{{ t('investment.ffe.rowsOf') }}</p>
                    <p v-if="error('rows')" class="fx-err" role="alert">{{ error('rows') }}</p>
                </SectionCard>

                <SectionCard v-bind="sections[1]">
                    <div class="sf-grid" data-dates>
                        <FormField :label="t('investment.fields.startDate')" for="ffe-start" required :error="error('dates.start')" class="c2">
                            <SelectField id="ffe-start" v-model="form.dates.start" :options="monthOptions" :invalid="!!error('dates.start')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.duration')" for="ffe-duration" required :error="error('dates.duration')" class="c2">
                            <NumberField id="ffe-duration" v-model="form.dates.duration" :min="0" :max="600" :decimals="0" :invalid="!!error('dates.duration') || lateEnd" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.endDate')" class="c2">
                            <div class="fx-readout inv-out" :class="{ bad: lateEnd }" data-ffe-end>{{ endText }}</div>
                        </FormField>
                        <FormField :label="t('investment.fields.executionMethod')" for="ffe-method" required :error="error('dates.method')" class="c3">
                            <SelectField id="ffe-method" v-model="form.dates.method" :options="executionOptions" :invalid="!!error('dates.method')" :disabled="locked" />
                        </FormField>
                    </div>
                </SectionCard>

                <SectionCard v-bind="sections[2]">
                    <div class="sf-grid" data-terms>
                        <PaymentTerms v-model="form.terms" :due-days="lists.dueDays" :errors="form.errors" error-prefix="terms" id="ffe-terms" :disabled="locked" />
                        <p class="inv-sub">{{ t('investment.fields.fundingStructure') }}</p>
                        <FundingBlock v-model="form.equity" :total="itemsTotal" id="ffe-funding" :invalid="!!error('equity')" :error="error('equity')" :disabled="locked" />
                    </div>
                    <LoanForm v-if="debt > 0" v-model="form.loans.ffe_cost" id="ffe-loan" :errors="form.errors" error-prefix="loans.ffe_cost" :disabled="locked" />
                </SectionCard>

                <div v-if="canEdit" class="sf-bar" data-savebar>
                    <p class="sf-bar-msg" :class="problems ? 'bad' : (form.isDirty ? 'dirty' : 'ok')" role="status">
                        <span class="sf-bar-dot" />
                        <span v-if="form.processing">{{ t('studies.saving') }}</span>
                        <span v-else-if="problems">{{ t('studies.fixErrors', { count: problems }) }}</span>
                        <span v-else-if="form.isDirty">{{ t('studies.unsaved') }}</span>
                        <span v-else>{{ t('studies.allSaved') }}</span>
                    </p>
                    <Link :href="route('studies.edit', study.id)" class="btn btn-line">{{ t('common.cancel') }}</Link>
                    <button type="submit" class="btn btn-line" :disabled="form.processing" data-save-stay><AppIcon name="check" :size="15" />{{ t('studies.save') }}</button>
                    <button type="button" class="btn" :disabled="form.processing" data-save-next @click="save('next')">
                        <AppIcon name="check" :size="15" />{{ t('studies.saveNext') }}
                    </button>
                </div>
                <div v-else class="sf-bar">
                    <p class="sf-bar-msg"><span class="sf-bar-dot" /><span>{{ t('studies.viewOnlyBar') }}</span></p>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
