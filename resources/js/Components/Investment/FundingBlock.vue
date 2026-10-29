<!--
  CFO Hotels — FundingBlock (how a cost is funded: equity and debt)
  Location: resources/js/Components/Investment/FundingBlock.vue
  <FundingBlock v-model="form.land.equity" :total="totalPurchaseCost" :invalid="!!form.errors['land.equity']" />
  Four boxes in the grid of the section: Equity Funding % (typed), Equity Amount, Debt Funding % and Debt Amount (worked out, as the old page did).
  The debt share is what equity does not fund; when it is above zero the page asks for the loan.
-->
<script setup>
import { computed } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { debtAmount, debtRate, equityAmount } from '@/Utils/investment';

const equity = defineModel({ type: [Number, String], default: 100 });
const props = defineProps({
    total: { type: Number, default: 0 },
    id: { type: String, default: 'funding' },
    invalid: { type: Boolean, default: false },
    error: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    debtLabel: { type: String, default: '' },
});

const { t } = useI18n();
const equityValue = computed(() => equityAmount(props.total, equity.value));
const debtValue = computed(() => debtAmount(props.total, equity.value));
</script>

<template>
    <FormField :label="t('investment.fields.equityFunding')" :for="`${id}-equity`" :error="error" class="c3" data-equity>
        <NumberField :id="`${id}-equity`" v-model="equity" unit="%" :min="0" :max="100" :invalid="invalid" :disabled="disabled" />
    </FormField>
    <FormField :label="t('investment.fields.equityAmount')" class="c3">
        <div class="fx-readout inv-out" data-equity-amount>{{ formatNumber(equityValue) }}</div>
    </FormField>
    <FormField :label="debtLabel || t('investment.fields.debtFunding')" class="c3">
        <div class="fx-readout inv-out" data-debt-rate>{{ formatNumber(debtRate(equity), 1) }}%</div>
    </FormField>
    <FormField :label="t('investment.fields.debtAmount')" class="c3">
        <div class="fx-readout inv-out" data-debt-amount>{{ formatNumber(debtValue) }}</div>
    </FormField>
</template>
