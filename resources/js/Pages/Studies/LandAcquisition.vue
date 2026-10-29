<!--
  CFO Hotels — Land & Construction Acquisition Cost ( /studies/{id}/land-acquisition-costs )
  Location: resources/js/Pages/Studies/LandAcquisition.vue
  Three sections, as in the old "Land & Construction Acquisition Cost Input Sheet":
    1 Land Acquisition Cost        the purchase (date, cost, payment method and terms), its funding and, when part is funded by debt, its loan
    2 Construction Cost [Hard Cost]  cost, contingency, start, duration, end, execution method, payment terms, funding and loan
    3 Construction Cost [Soft Cost]  the same without funding and loan (funded by equity)
  The end of a construction is its start plus its duration and may not be after the opening month.
  Server side: App\Http\Controllers\Hospitality\LandAcquisitionController, rules in App\Http\Requests\Investment\LandAcquisitionRequest.
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
import PurchaseFields from '@/Components/Investment/PurchaseFields.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { debtRate, endIndex, totalCost } from '@/Utils/investment';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
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

const monthOptions = computed(() => props.months.map((m) => ({ value: m.index, text: m.label })));
const executionOptions = (kind) => (kind === 'hard' ? props.lists.hardExecution : props.lists.softExecution).map((v) => ({ value: v, text: t(`investment.execution.${v}`) }));
const hardTotal = computed(() => totalCost(form.hard.cost, form.hard.contingency));
const softTotal = computed(() => totalCost(form.soft.cost, form.soft.contingency));
const landDebt = computed(() => debtRate(form.land.equity));
const hardDebt = computed(() => debtRate(form.hard.equity));
const endText = (kind) => {
    const end = endIndex(form[kind].start, form[kind].duration);

    return props.endLabels[end] ?? '';
};
const lateEnd = (kind) => endIndex(form[kind].start, form[kind].duration) > props.opening;

const sections = computed(() => [
    { id: 'inv-land', number: 1, title: t('investment.land.landTitle'), text: t('investment.land.landLead') },
    { id: 'inv-hard', number: 2, title: t('investment.land.hardTitle'), text: t('investment.land.hardLead') },
    { id: 'inv-soft', number: 3, title: t('investment.land.softTitle'), text: t('investment.land.softLead') },
]);
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.title, state: '' })));

const error = (key) => form.errors[key] ?? '';
const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.investment.land.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('investment.land.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('investment.land.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="investment.land" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-land-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-bind="sections[0]">
                    <PurchaseFields v-model="form.land" kind="land" :months="months" :intervals="lists.intervals" :collection-due="lists.collectionDue" :errors="form.errors" error-prefix="land" :disabled="locked" />
                    <LoanForm v-if="landDebt > 0" v-model="form.loans.land" id="land-loan" :errors="form.errors" error-prefix="loans.land" :disabled="locked" />
                </SectionCard>

                <SectionCard v-bind="sections[1]">
                    <div class="sf-grid" data-hard>
                        <FormField :label="t('investment.fields.constructionCost')" for="hard-cost" required :error="error('hard.cost')" class="c2">
                            <NumberField id="hard-cost" v-model="form.hard.cost" :min="0" :max="1000000000000" :invalid="!!error('hard.cost')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.contingency')" for="hard-contingency" :error="error('hard.contingency')" class="c2">
                            <NumberField id="hard-contingency" v-model="form.hard.contingency" unit="%" :min="0" :max="100" :invalid="!!error('hard.contingency')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.totalConstruction')" class="c2">
                            <div class="fx-readout inv-out strong" data-hard-total>{{ formatNumber(hardTotal) }}</div>
                        </FormField>
                        <FormField :label="t('investment.fields.startDate')" for="hard-start" required :error="error('hard.start')" class="c2">
                            <SelectField id="hard-start" v-model="form.hard.start" :options="monthOptions" :invalid="!!error('hard.start')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.duration')" for="hard-duration" required :error="error('hard.duration')" class="c2">
                            <NumberField id="hard-duration" v-model="form.hard.duration" :min="0" :max="600" :decimals="0" :invalid="!!error('hard.duration') || lateEnd('hard')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.constructionEnd')" class="c2">
                            <div class="fx-readout inv-out" :class="{ bad: lateEnd('hard') }" data-hard-end>{{ endText('hard') }}</div>
                        </FormField>
                        <FormField :label="t('investment.fields.executionMethod')" for="hard-method" required :error="error('hard.method')" class="c3">
                            <SelectField id="hard-method" v-model="form.hard.method" :options="executionOptions('hard')" :invalid="!!error('hard.method')" :disabled="locked" />
                        </FormField>
                        <PaymentTerms v-model="form.hard" :due-days="lists.dueDays" :errors="form.errors" error-prefix="hard" id="hard-terms" :disabled="locked" />
                        <p class="inv-sub">{{ t('investment.fields.fundingStructure') }}</p>
                        <FundingBlock v-model="form.hard.equity" :total="hardTotal" id="hard-funding" :invalid="!!error('hard.equity')" :error="error('hard.equity')" :disabled="locked" />
                    </div>
                    <LoanForm v-if="hardDebt > 0" v-model="form.loans.hard_cost_construction" id="hard-loan" :errors="form.errors" error-prefix="loans.hard_cost_construction" :disabled="locked" />
                </SectionCard>

                <SectionCard v-bind="sections[2]">
                    <div class="sf-grid" data-soft>
                        <FormField :label="t('investment.fields.softCost')" for="soft-cost" required :error="error('soft.cost')" class="c2">
                            <NumberField id="soft-cost" v-model="form.soft.cost" :min="0" :max="1000000000000" :invalid="!!error('soft.cost')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.contingency')" for="soft-contingency" :error="error('soft.contingency')" class="c2">
                            <NumberField id="soft-contingency" v-model="form.soft.contingency" unit="%" :min="0" :max="100" :invalid="!!error('soft.contingency')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.totalSoft')" class="c2">
                            <div class="fx-readout inv-out strong" data-soft-total>{{ formatNumber(softTotal) }}</div>
                        </FormField>
                        <FormField :label="t('investment.fields.startDate')" for="soft-start" required :error="error('soft.start')" class="c2">
                            <SelectField id="soft-start" v-model="form.soft.start" :options="monthOptions" :invalid="!!error('soft.start')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.duration')" for="soft-duration" required :error="error('soft.duration')" class="c2">
                            <NumberField id="soft-duration" v-model="form.soft.duration" :min="0" :max="600" :decimals="0" :invalid="!!error('soft.duration') || lateEnd('soft')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.constructionEnd')" class="c2">
                            <div class="fx-readout inv-out" :class="{ bad: lateEnd('soft') }" data-soft-end>{{ endText('soft') }}</div>
                        </FormField>
                        <FormField :label="t('investment.fields.executionMethod')" for="soft-method" required :error="error('soft.method')" class="c3">
                            <SelectField id="soft-method" v-model="form.soft.method" :options="executionOptions('soft')" :invalid="!!error('soft.method')" :disabled="locked" />
                        </FormField>
                        <PaymentTerms v-model="form.soft" :due-days="lists.dueDays" :errors="form.errors" error-prefix="soft" id="soft-terms" :disabled="locked" />
                    </div>
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
