<!--
  CFO Hotels — NumberField (one number in one box, with its unit)
  Location: resources/js/Components/Form/NumberField.vue
  <NumberField id="s-tax" v-model="form.corporate_taxes_rate" unit="%" :min="0" :max="100" :decimals="2" />
  · The value is a number (or null while the box is empty), never text.
  · Only digits (and one decimal point) can be typed; Arabic-Indic digits are turned into 0-9 as they are typed.
  · Numbers always read left-to-right, also on an Arabic page. Out-of-range values are pulled back into range when the box is left.
  · decimals="0" = whole numbers only.
  Single numbers use this box. A number in a row of cells (a figure per year or month) uses CopyInput instead (docs/NUMBER_INPUT_RULE.md).
-->
<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    modelValue: { type: [Number, String], default: null },
    id: { type: String, default: undefined },
    unit: { type: String, default: '' },
    min: { type: Number, default: undefined },
    max: { type: Number, default: undefined },
    decimals: { type: Number, default: 2 },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    placeholder: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const arabicDigits = '٠١٢٣٤٥٦٧٨٩';
const show = (value) => (value === null || value === undefined || value === '' ? '' : String(value));
const read = (text) => {
    if (text === '' || text === '.') return null;
    const n = Number(text);

    return Number.isFinite(n) ? n : null;
};

const text = ref(show(props.modelValue));

watch(() => props.modelValue, (value) => {
    if (read(text.value) !== (value === '' ? null : (value === null ? null : Number(value)))) {
        text.value = show(value);
    }
});

function clean(raw) {
    let s = String(raw).replace(/[٠-٩]/g, (d) => String(arabicDigits.indexOf(d))).replace(/٫/g, '.');
    s = s.replace(props.decimals === 0 ? /[^\d]/g : /[^\d.]/g, '');
    if (props.decimals > 0) {
        const [whole, ...rest] = s.split('.');
        s = rest.length ? `${whole}.${rest.join('').slice(0, props.decimals)}` : whole;
    }

    return s;
}

function onInput(event) {
    text.value = clean(event.target.value);
    event.target.value = text.value;
    emit('update:modelValue', read(text.value));
}

function onBlur() {
    let n = read(text.value);
    if (n === null) {
        text.value = '';

        return;
    }
    if (props.min !== undefined && n < props.min) n = props.min;
    if (props.max !== undefined && n > props.max) n = props.max;
    text.value = String(n);
    emit('update:modelValue', n);
}
</script>

<template>
    <div class="fx-num" :class="{ nounit: !unit }">
        <input
            :id="id" class="inp" type="text" :inputmode="decimals === 0 ? 'numeric' : 'decimal'" autocomplete="off" dir="ltr"
            :value="text" :disabled="disabled" :placeholder="placeholder" :aria-invalid="invalid ? 'true' : undefined"
            @input="onInput" @blur="onBlur"
        >
        <span v-if="unit" class="fx-unit" aria-hidden="true">{{ unit }}</span>
    </div>
</template>
