<!--
  CFO Hotels — CollectionTerms (when the hotel gets paid)
  Location: resources/js/Components/Revenue/CollectionTerms.vue
  <CollectionTerms v-model="form.collection.general" :errors="form.errors" error-prefix="collection.general" />
  Either the system default (money comes in every month / quarter / half year / year), or four rates that add up to 100 %, each paid
  a number of days after the sale. The total is shown while typing.
-->
<script setup>
import { computed } from 'vue';
import ChoiceCards from '@/Components/Form/ChoiceCards.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import { useI18n } from '@/lang/i18n';

const terms = defineModel({ type: Object, required: true });
const props = defineProps({
    disabled: { type: Boolean, default: false },
    errors: { type: Object, default: () => ({}) },
    errorPrefix: { type: String, default: '' },
});

const { t } = useI18n();
const types = computed(() => ['system_default', 'customize'].map((v) => ({ value: v, title: t(`revenue.terms.${v}`), text: t(`revenue.terms.${v}Text`) })));
const intervals = computed(() => ['monthly', 'quarterly', 'semi-annually', 'annually'].map((v) => ({ value: v, text: t(`revenue.terms.interval.${v}`) })));
const dueOptions = [0, 15, 30, 45, 60, 75, 90, 120, 150, 180].map((v) => ({ value: v, text: String(v) }));
const total = computed(() => terms.value.rates.reduce((sum, n) => sum + (Number(n) || 0), 0));
const balanced = computed(() => Math.abs(total.value - 100) < 0.005);
const error = (key) => props.errors[`${props.errorPrefix}.${key}`];
</script>

<template>
    <div class="rv-terms">
        <ChoiceCards v-model="terms.type" :options="types" :legend="t('revenue.terms.title')" :disabled="disabled" />
        <div v-if="terms.type === 'system_default'" class="rv-terms-body">
            <label class="rv-terms-label">{{ t('revenue.terms.collected') }}
                <SelectField v-model="terms.interval" :options="intervals" :disabled="disabled" />
            </label>
        </div>
        <div v-else class="rv-terms-body">
            <div class="rv-instal" role="group" :aria-label="t('revenue.terms.customize')">
                <div v-for="(_, i) in terms.rates" :key="i" class="rv-instal-row">
                    <b>{{ t('revenue.terms.payment', { n: i + 1 }) }}</b>
                    <label>{{ t('revenue.terms.rate') }}
                        <span class="rv-pct" dir="ltr"><input v-model.number="terms.rates[i]" class="inp" dir="ltr" type="number" step="any" min="0" max="100" :disabled="disabled" :aria-invalid="!!error(`rates.${i}`)"><i aria-hidden="true">%</i></span>
                    </label>
                    <label>{{ t('revenue.terms.due') }}
                        <SelectField v-model="terms.due[i]" :options="dueOptions" :disabled="disabled" />
                    </label>
                </div>
            </div>
            <p class="rv-total" :class="balanced ? 'ok' : 'bad'" role="status"><span dir="ltr">{{ total }}%</span> {{ balanced ? t('revenue.terms.balanced') : t('revenue.terms.mustBe100') }}</p>
        </div>
    </div>
</template>
