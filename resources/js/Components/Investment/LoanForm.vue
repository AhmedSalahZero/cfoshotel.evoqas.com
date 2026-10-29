<!--
  CFO Hotels — LoanForm (the loan that funds a part of a cost)
  Location: resources/js/Components/Investment/LoanForm.vue
  <LoanForm v-model="form.loans.land" :errors="form.errors" error-prefix="loans.land" id="land-loan" />
  The same form on every investment page (resources/views/loan-form.blade.php of the old application): Fixed Loan Type, Base Rate %, Margin Rate %,
  Pricing % (base + margin, worked out), Tenor (months), Grace Period and Capitalization Type (only for a grace type), Installment Payment Interval,
  and the Step-up / Step-down rate and interval (only for a step type). The parent shows it when the debt share is above zero.
  What the loan type asks for is in resources/js/Utils/investment.js.
-->
<script setup>
import { computed, watch } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { LOAN_INSTALLMENT, LOAN_STEP, LOAN_TYPES, graceLimit, loanFlags, pricing } from '@/Utils/investment';

const loan = defineModel({ type: Object, required: true });
const props = defineProps({
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
    id: { type: String, default: 'loan' },
    disabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const flags = computed(() => loanFlags(loan.value.loan_type));
const typeOptions = LOAN_TYPES.map((v) => ({ value: v, text: v.toUpperCase() }));
const installmentOptions = computed(() => LOAN_INSTALLMENT.map((v) => ({ value: v, text: t(`investment.loan.interval.${v}`) })));
const stepOptions = computed(() => LOAN_STEP.map((v) => ({ value: v, text: t(`investment.loan.interval.${v}`) })));
const price = computed(() => pricing(loan.value.base_rate, loan.value.margin_rate));
const error = (key) => props.errors[`${props.errorPrefix}.${key}`] ?? '';

// a type that does not use a field sets it to nothing, and a grace period leaves two months of the tenor (as the old script did)
watch(() => loan.value.loan_type, () => {
    if (!flags.value.stepUp) loan.value.step_up_rate = 0;
    if (!flags.value.stepDown) loan.value.step_down_rate = 0;
    if (!flags.value.grace) loan.value.grace_period = 0;
});
watch(() => [loan.value.grace_period, loan.value.period], () => {
    const limited = graceLimit(loan.value.grace_period, loan.value.period);
    if (limited !== Number(loan.value.grace_period || 0)) loan.value.grace_period = limited;
});
</script>

<template>
    <div class="inv-loan" data-loan :aria-label="t('investment.loan.title')" role="group">
        <div class="inv-loan-head">
            <h4>{{ t('investment.loan.title') }}</h4>
            <p>{{ t('investment.loan.lead') }}</p>
        </div>
        <div class="sf-grid">
            <FormField :label="t('investment.loan.loan_type')" :for="`${id}-type`" :error="error('loan_type')" class="c3">
                <SelectField :id="`${id}-type`" v-model="loan.loan_type" :options="typeOptions" :invalid="!!error('loan_type')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.loan.base_rate')" :for="`${id}-base`" :error="error('base_rate')" class="c3">
                <NumberField :id="`${id}-base`" v-model="loan.base_rate" unit="%" :min="0" :max="100" :decimals="4" :invalid="!!error('base_rate')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.loan.margin_rate')" :for="`${id}-margin`" :error="error('margin_rate')" class="c3">
                <NumberField :id="`${id}-margin`" v-model="loan.margin_rate" unit="%" :min="0" :max="100" :decimals="4" :invalid="!!error('margin_rate')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.loan.pricing')" class="c3">
                <div class="fx-readout inv-out" data-pricing>{{ formatNumber(price, 2) }}%</div>
            </FormField>
            <FormField :label="t('investment.loan.period')" :for="`${id}-period`" :error="error('period')" class="c3">
                <NumberField :id="`${id}-period`" v-model="loan.period" :min="0" :max="600" :decimals="0" :invalid="!!error('period')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.loan.installment_interval')" :for="`${id}-installment`" :error="error('installment_interval')" class="c3">
                <SelectField :id="`${id}-installment`" v-model="loan.installment_interval" :options="installmentOptions" :invalid="!!error('installment_interval')" :disabled="disabled" />
            </FormField>
            <template v-if="flags.grace">
                <FormField :label="t('investment.loan.grace_period')" :for="`${id}-grace`" :error="error('grace_period')" class="c3" data-grace>
                    <NumberField :id="`${id}-grace`" v-model="loan.grace_period" :min="0" :max="600" :decimals="0" :invalid="!!error('grace_period')" :disabled="disabled" />
                </FormField>
                <FormField :label="t('investment.loan.capitalization')" class="c3">
                    <div class="fx-readout inv-out" data-capitalization>{{ t(`investment.loan.${flags.capitalization}`) }}</div>
                </FormField>
            </template>
            <template v-if="flags.stepUp">
                <FormField :label="t('investment.loan.step_up_rate')" :for="`${id}-up`" :error="error('step_up_rate')" class="c3" data-step-up>
                    <NumberField :id="`${id}-up`" v-model="loan.step_up_rate" unit="%" :min="0" :max="100" :decimals="4" :invalid="!!error('step_up_rate')" :disabled="disabled" />
                </FormField>
                <FormField :label="t('investment.loan.step_up_interval')" :for="`${id}-up-interval`" :error="error('step_up_interval')" class="c3">
                    <SelectField :id="`${id}-up-interval`" v-model="loan.step_up_interval" :options="stepOptions" :invalid="!!error('step_up_interval')" :disabled="disabled" />
                </FormField>
            </template>
            <template v-if="flags.stepDown">
                <FormField :label="t('investment.loan.step_down_rate')" :for="`${id}-down`" :error="error('step_down_rate')" :hint="t('investment.loan.negativeHint')" class="c3" data-step-down>
                    <input :id="`${id}-down`" v-model.number="loan.step_down_rate" class="inp" dir="ltr" type="number" step="any" min="-100" max="0" :disabled="disabled" :aria-invalid="!!error('step_down_rate') || undefined">
                </FormField>
                <FormField :label="t('investment.loan.step_down_interval')" :for="`${id}-down-interval`" :error="error('step_down_interval')" class="c3">
                    <SelectField :id="`${id}-down-interval`" v-model="loan.step_down_interval" :options="stepOptions" :invalid="!!error('step_down_interval')" :disabled="disabled" />
                </FormField>
            </template>
        </div>
    </div>
</template>
