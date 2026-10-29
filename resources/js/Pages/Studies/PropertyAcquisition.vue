<!--
  CFO Hotels — Property Acquisition Cost ( /studies/{id}/property-acquisition-costs )
  Location: resources/js/Pages/Studies/PropertyAcquisition.vue
  The "Property Acquisition Cost Input Sheet" of the old application, in three sections:
    1 Property Acquisition Cost   the purchase (date, cost, contingency, payment method and terms), its funding and, when part is funded by debt, its loan
    2 Cost breakdown              the share (%) of the total purchase cost that is land, building and furniture & equipment (adding up to 100 %),
                                  the amount of each, and the years the building and the furniture & equipment are depreciated over
    3 Replacement                 Refurbishment & Renovation of the building and Furniture, Fixture & Equipment: a rate of cost and an interval in years
  Server side: App\Http\Controllers\Hospitality\PropertyAcquisitionController, rules in App\Http\Requests\Investment\PropertyAcquisitionRequest.
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
import LoanForm from '@/Components/Investment/LoanForm.vue';
import PurchaseFields from '@/Components/Investment/PurchaseFields.vue';
import StudyTabs from '@/Components/Studies/StudyTabs.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { debtRate, ratesAddUp, sum, totalCost } from '@/Utils/investment';

const props = defineProps({
    study: { type: Object, required: true },
    menu: { type: Array, default: () => [] },
    access: { type: String, default: 'owner' },
    readOnly: { type: Boolean, default: false },
    months: { type: Array, default: () => [] },
    lists: { type: Object, required: true },
    form: { type: Object, required: true },
});

const { t } = useI18n();
const canEdit = computed(() => !props.readOnly && ['owner', 'admin', 'edit'].includes(props.access));
const locked = computed(() => !canEdit.value);
const form = useForm(JSON.parse(JSON.stringify(props.form)));

const total = computed(() => totalCost(form.property.cost, form.property.contingency));
const debt = computed(() => debtRate(form.property.equity));
const shareTotal = computed(() => sum(form.breakdown.map((line) => line.percentage)));
const sharesOk = computed(() => ratesAddUp(form.breakdown.map((line) => line.percentage)));
const amount = (line) => total.value * (Number(line.percentage) || 0) / 100;
const years = (list) => list.map((y) => ({ value: y, text: t('investment.years', { n: y }) }));
const depreciationOptions = [years(props.lists.depreciation), years(props.lists.buildingDepreciation)];
const replacementOptions = years(props.lists.replacement);
const lineName = (i) => t(`investment.property.lines.${i}`);

const sections = computed(() => [
    { id: 'inv-property', number: 1, title: t('investment.property.sectionTitle'), text: t('investment.property.sectionLead') },
    { id: 'inv-breakdown', number: 2, title: t('investment.property.breakdownTitle'), text: t('investment.property.breakdownLead') },
    { id: 'inv-replacement', number: 3, title: t('investment.property.replacementTitle'), text: t('investment.property.replacementLead') },
]);
const nav = computed(() => sections.value.map((s) => ({ id: s.id, title: s.title, state: '' })));

const error = (key) => form.errors[key] ?? '';
const problems = computed(() => Object.keys(form.errors).length);
const errorList = computed(() => [...new Set(Object.values(form.errors))].slice(0, 6));

// after = where the person goes once it is saved: 'stay' (this page) or 'next' (the next page of the study, in the order of the previous application)
function save(after = 'stay') {
    if (!canEdit.value) return;
    form.transform((data) => ({ ...data, after })).put(route('studies.investment.property.update', props.study.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout :title="t('investment.property.title')" active="studies" accent="violet">
        <div class="pagehead">
            <div>
                <h2>{{ study.name }}</h2>
                <p class="lead mb0">{{ t('investment.property.lead') }}</p>
            </div>
        </div>
        <StudyTabs :menu="menu" current="investment.property" />

        <div v-if="readOnly" class="note" role="status"><AppIcon name="lock" :size="14" /> {{ t('studies.companyReadOnly') }}</div>
        <div v-else-if="locked" class="note" role="status"><AppIcon name="eye" :size="14" /> {{ t('studies.viewOnly') }}</div>
        <div v-if="problems" class="note bad" role="alert">
            <b>{{ t('studies.fixErrors', { count: problems }) }}</b>
            <ul class="rv-errors"><li v-for="e in errorList" :key="e">{{ e }}</li></ul>
        </div>

        <form class="sf" novalidate data-property-form @submit.prevent="save()">
            <SectionNav :items="nav" :title="t('studies.sections.title')" />

            <div class="sf-main">
                <SectionCard v-bind="sections[0]">
                    <PurchaseFields v-model="form.property" kind="property" :months="months" :intervals="lists.intervals" :collection-due="lists.collectionDue" :errors="form.errors" error-prefix="property" :disabled="locked" />
                    <LoanForm v-if="debt > 0" v-model="form.loans.property_loan" id="property-loan" :errors="form.errors" error-prefix="loans.property_loan" :disabled="locked" />
                </SectionCard>

                <SectionCard v-bind="sections[1]">
                    <div class="rv-scroll" role="region" :aria-label="sections[1].title" tabindex="0">
                        <table class="ex-grid inv-items" data-breakdown>
                            <thead>
                                <tr>
                                    <th scope="col" class="rv-rowhead">{{ t('investment.fields.itemName') }}</th>
                                    <th scope="col">{{ t('investment.fields.asPercentage') }}</th>
                                    <th scope="col">{{ t('investment.fields.itemValue') }}</th>
                                    <th scope="col">{{ t('investment.fields.depreciationDuration') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, i) in form.breakdown" :key="i" data-breakdown-row>
                                    <th scope="row" class="rv-rowhead">{{ lineName(i) }}</th>
                                    <td :class="{ bad: !!error(`breakdown.${i}.percentage`) }">
                                        <NumberField v-model="line.percentage" unit="%" :min="0" :max="100" :invalid="!!error(`breakdown.${i}.percentage`)" :disabled="locked" />
                                    </td>
                                    <td class="rv-num" dir="ltr" data-item-value>{{ formatNumber(amount(line)) }}</td>
                                    <td :class="{ bad: !!error(`breakdown.${i}.depreciation`) }">
                                        <SelectField v-if="i > 0" v-model="line.depreciation" :options="depreciationOptions[i === 1 ? 1 : 0]" :invalid="!!error(`breakdown.${i}.depreciation`)" :disabled="locked" />
                                        <span v-else class="mute">—</span>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="ex-total">
                                    <th scope="row" class="rv-rowhead">{{ t('investment.fields.total') }}</th>
                                    <td class="rv-num" :class="{ bad: !sharesOk }" dir="ltr" data-share-total>{{ formatNumber(shareTotal, 1) }}%</td>
                                    <td class="rv-num" dir="ltr" data-value-total>{{ formatNumber(sum(form.breakdown.map(amount))) }}</td>
                                    <td />
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <p class="inv-total-line" :class="sharesOk ? 'ok' : 'bad'" role="status" data-shares-note>{{ sharesOk ? t('investment.balanced') : t('investment.mustBe100') }}</p>
                    <p v-if="error('breakdown')" class="fx-err" role="alert">{{ error('breakdown') }}</p>
                </SectionCard>

                <SectionCard v-bind="sections[2]">
                    <div class="sf-grid" data-replacement>
                        <p class="inv-sub">{{ t('investment.property.replacementBuilding') }}</p>
                        <FormField :label="t('investment.fields.replacementRate')" for="rep-rate" :error="error('replacement.rate')" class="c3">
                            <NumberField id="rep-rate" v-model="form.replacement.rate" unit="%" :min="0" :max="100" :invalid="!!error('replacement.rate')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.replacementInterval')" for="rep-interval" :error="error('replacement.interval')" class="c3">
                            <SelectField id="rep-interval" v-model="form.replacement.interval" :options="replacementOptions" :invalid="!!error('replacement.interval')" :disabled="locked" />
                        </FormField>
                        <p class="inv-sub">{{ t('investment.property.replacementFfe') }}</p>
                        <FormField :label="t('investment.fields.replacementRate')" for="rep-ffe-rate" :error="error('replacement.ffeRate')" class="c3">
                            <NumberField id="rep-ffe-rate" v-model="form.replacement.ffeRate" unit="%" :min="0" :max="100" :invalid="!!error('replacement.ffeRate')" :disabled="locked" />
                        </FormField>
                        <FormField :label="t('investment.fields.replacementInterval')" for="rep-ffe-interval" :error="error('replacement.ffeInterval')" class="c3">
                            <SelectField id="rep-ffe-interval" v-model="form.replacement.ffeInterval" :options="replacementOptions" :invalid="!!error('replacement.ffeInterval')" :disabled="locked" />
                        </FormField>
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
