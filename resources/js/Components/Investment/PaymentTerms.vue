<!--
  CFO Hotels — PaymentTerms (how a construction or FF&E cost is paid)
  Location: resources/js/Components/Investment/PaymentTerms.vue
  <PaymentTerms v-model="form.hard" :due-days="lists.dueDays" :errors="form.errors" error-prefix="hard" />
  Down payment %, Balance Rate One % with its due days, and Balance Rate Two % (what is left of 100 %, worked out) with its due days.
  The model needs the keys down, one, dueOne and dueTwo.
-->
<script setup>
import { computed } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { balanceRate } from '@/Utils/investment';

const terms = defineModel({ type: Object, required: true });
const props = defineProps({
    dueDays: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
    id: { type: String, default: 'terms' },
    disabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const options = computed(() => props.dueDays.map((d) => ({ value: d, text: String(d) })));
const error = (key) => props.errors[`${props.errorPrefix}.${key}`] ?? '';
const balanceTwo = computed(() => balanceRate(terms.value.down, terms.value.one));
</script>

<template>
    <p class="inv-sub">{{ t('investment.fields.paymentTerms') }}</p>
    <FormField :label="t('investment.fields.downPayment')" :for="`${id}-down`" :error="error('down')" class="c2">
        <NumberField :id="`${id}-down`" v-model="terms.down" unit="%" :min="0" :max="100" :invalid="!!error('down')" :disabled="disabled" />
    </FormField>
    <FormField :label="t('investment.fields.balanceOne')" :for="`${id}-one`" :error="error('one')" class="c2">
        <NumberField :id="`${id}-one`" v-model="terms.one" unit="%" :min="0" :max="100" :invalid="!!error('one')" :disabled="disabled" />
    </FormField>
    <FormField :label="t('investment.fields.dueOne')" :for="`${id}-due-one`" :error="error('dueOne')" class="c2">
        <SelectField :id="`${id}-due-one`" v-model="terms.dueOne" :options="options" :invalid="!!error('dueOne')" :disabled="disabled" />
    </FormField>
    <FormField :label="t('investment.fields.balanceTwo')" class="c3">
        <div class="fx-readout inv-out" data-balance-two>{{ formatNumber(balanceTwo, 1) }}%</div>
    </FormField>
    <FormField :label="t('investment.fields.dueTwo')" :for="`${id}-due-two`" :error="error('dueTwo')" class="c3">
        <SelectField :id="`${id}-due-two`" v-model="terms.dueTwo" :options="options" :invalid="!!error('dueTwo')" :disabled="disabled" />
    </FormField>
</template>
