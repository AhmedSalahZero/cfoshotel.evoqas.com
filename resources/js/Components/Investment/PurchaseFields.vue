<!--
  CFO Hotels — PurchaseFields (the purchase of the land, or of the property)
  Location: resources/js/Components/Investment/PurchaseFields.vue
  <PurchaseFields v-model="form.land" kind="land" :months="months" :collection-due="lists.collectionDue" :intervals="lists.intervals" :errors="form.errors" error-prefix="land" />
  Purchase Date, Purchase Cost, Contingency Rate (%), Total Purchase Cost (worked out), the payment method (Cash / Installment / Customize) and what each asks for:
  installment = First and Second Down payment %, After Month, Balance Rate (what is left), Installment Count and Interval;
  customize = four Collection Rates % adding up to 100 with their Due In Days; then the funding (equity and debt).
  The page puts the loan form after this when the debt share is above zero.
-->
<script setup>
import { computed } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import FundingBlock from '@/Components/Investment/FundingBlock.vue';
import { useI18n } from '@/lang/i18n';
import { formatNumber } from '@/Utils/format';
import { balanceRate, ratesAddUp, sum, totalCost } from '@/Utils/investment';

const block = defineModel({ type: Object, required: true });
const props = defineProps({
    kind: { type: String, default: 'land' },
    months: { type: Array, required: true },
    methods: { type: Array, default: () => ['cash', 'installment', 'customize'] },
    intervals: { type: Array, default: () => ['monthly', 'quarterly', 'semi-annually', 'annually'] },
    collectionDue: { type: Array, default: () => [0, 30, 60, 90, 120, 150, 180, 270, 360] },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: 'land' },
    disabled: { type: Boolean, default: false },
});

const { t } = useI18n();
const id = computed(() => `${props.kind}-purchase`);
const total = computed(() => totalCost(block.value.cost, block.value.contingency));
const balance = computed(() => balanceRate(block.value.first, block.value.second));
const rateTotal = computed(() => sum(block.value.terms.rates));
const balanced = computed(() => ratesAddUp(block.value.terms.rates));
const monthOptions = computed(() => props.months.map((m) => ({ value: m.index, text: m.label })));
const methodOptions = computed(() => props.methods.map((v) => ({ value: v, text: t(`investment.methods.${v}`) })));
const intervalOptions = computed(() => props.intervals.map((v) => ({ value: v, text: t(`investment.intervals.${v}`) })));
const dueOptions = computed(() => props.collectionDue.map((d) => ({ value: d, text: d === 0 ? '0' : (d === 30 ? t('investment.monthOne') : t('investment.monthMany', { n: d / 30 })) })));
const error = (key) => props.errors[`${props.errorPrefix}.${key}`] ?? '';
const bad = (key) => !!error(key);
defineExpose({ total });
</script>

<template>
    <div class="sf-grid" :data-purchase="kind">
        <FormField :label="t('investment.fields.purchaseDate')" :for="`${id}-date`" required :error="error('date')" class="c2">
            <SelectField :id="`${id}-date`" v-model="block.date" :options="monthOptions" :invalid="bad('date')" :disabled="disabled" />
        </FormField>
        <FormField :label="t('investment.fields.purchaseCost')" :for="`${id}-cost`" required :error="error('cost')" class="c2">
            <NumberField :id="`${id}-cost`" v-model="block.cost" :min="0" :max="1000000000000" :invalid="bad('cost')" :disabled="disabled" />
        </FormField>
        <FormField :label="t('investment.fields.contingency')" :for="`${id}-contingency`" :error="error('contingency')" class="c2">
            <NumberField :id="`${id}-contingency`" v-model="block.contingency" unit="%" :min="0" :max="100" :invalid="bad('contingency')" :disabled="disabled" />
        </FormField>
        <FormField :label="t('investment.fields.totalPurchase')" class="c3">
            <div class="fx-readout inv-out strong" data-total-purchase>{{ formatNumber(total) }}</div>
        </FormField>
        <FormField :label="t(`investment.fields.${kind}Method`)" :for="`${id}-method`" required :error="error('method')" class="c3">
            <SelectField :id="`${id}-method`" v-model="block.method" :options="methodOptions" :invalid="bad('method')" :disabled="disabled" />
        </FormField>

        <template v-if="block.method === 'installment'">
            <p class="inv-sub">{{ t('investment.methods.installment') }}</p>
            <FormField :label="t('investment.fields.firstDown')" :for="`${id}-first`" :error="error('first')" class="c2">
                <NumberField :id="`${id}-first`" v-model="block.first" unit="%" :min="0" :max="100" :invalid="bad('first')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.fields.secondDown')" :for="`${id}-second`" :error="error('second')" class="c2">
                <NumberField :id="`${id}-second`" v-model="block.second" unit="%" :min="0" :max="100" :invalid="bad('second')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.fields.afterMonth')" :for="`${id}-after`" :error="error('after')" class="c2">
                <NumberField :id="`${id}-after`" v-model="block.after" :min="0" :max="600" :decimals="0" :invalid="bad('after')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.fields.balanceRate')" class="c2">
                <div class="fx-readout inv-out" data-balance>{{ formatNumber(balance, 1) }}%</div>
            </FormField>
            <FormField :label="t('investment.fields.installmentCount')" :for="`${id}-count`" :error="error('count')" class="c2">
                <NumberField :id="`${id}-count`" v-model="block.count" :min="1" :max="600" :decimals="0" :invalid="bad('count')" :disabled="disabled" />
            </FormField>
            <FormField :label="t('investment.fields.installmentInterval')" :for="`${id}-interval`" required :error="error('interval')" class="c2">
                <SelectField :id="`${id}-interval`" v-model="block.interval" :options="intervalOptions" :invalid="bad('interval')" :disabled="disabled" />
            </FormField>
        </template>

        <template v-if="block.method === 'customize'">
            <p class="inv-sub">{{ t('investment.methods.customize') }}</p>
            <div class="c6" data-collection>
                <div class="inv-rates">
                    <div v-for="(_, i) in block.terms.rates" :key="i" class="inv-rate">
                        <b>{{ t('investment.payment', { n: i + 1 }) }}</b>
                        <FormField :label="t('investment.fields.collectionRate')" :for="`${id}-rate-${i}`" :error="error(`terms.rates.${i}`)">
                            <NumberField :id="`${id}-rate-${i}`" v-model="block.terms.rates[i]" unit="%" :min="0" :max="100" :invalid="bad(`terms.rates.${i}`)" :disabled="disabled" />
                        </FormField>
                        <FormField :label="t('investment.fields.dueInDays')" :for="`${id}-due-${i}`" :error="error(`terms.due.${i}`)">
                            <SelectField :id="`${id}-due-${i}`" v-model="block.terms.due[i]" :options="dueOptions" :invalid="bad(`terms.due.${i}`)" :disabled="disabled" />
                        </FormField>
                    </div>
                </div>
                <p class="inv-total-line" :class="balanced ? 'ok' : 'bad'" role="status" data-rates-total>
                    {{ t('investment.fields.total') }}: <span dir="ltr">{{ formatNumber(rateTotal, 1) }}%</span> {{ balanced ? t('investment.balanced') : t('investment.mustBe100') }}
                </p>
                <p v-if="error('terms.rates')" class="fx-err" role="alert">{{ error('terms.rates') }}</p>
            </div>
        </template>

        <p class="inv-sub">{{ t('investment.fields.fundingStructure') }}</p>
        <FundingBlock v-model="block.equity" :total="total" :id="id" :debt-label="t('investment.fields.debtFundingPct')" :invalid="bad('equity')" :error="error('equity')" :disabled="disabled" />
    </div>
</template>
