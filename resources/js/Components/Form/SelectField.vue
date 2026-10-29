<!--
  CFO Hotels — SelectField (a drop-down in the approved look)
  Location: resources/js/Components/Form/SelectField.vue
  <SelectField id="s-fy" v-model="form.financial_year_start_month" :options="[{ value: 'january', text: 'January' }]" :placeholder="a translated text" />
  options = [{ value, text, disabled? }]. The native drop-down is used on purpose: it is the best one on phones and tablets.
-->
<script setup>
defineProps({
    modelValue: { type: [String, Number], default: '' },
    options: { type: Array, required: true },
    id: { type: String, default: undefined },
    placeholder: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <select
        :id="id" class="sel" :value="modelValue ?? ''" :disabled="disabled" :aria-invalid="invalid ? 'true' : undefined"
        @change="emit('update:modelValue', $event.target.value === '' ? null : (options.some((o) => typeof o.value === 'number') ? Number($event.target.value) : $event.target.value))"
    >
        <option v-if="placeholder" value="">{{ placeholder }}</option>
        <option v-for="o in options" :key="o.value" :value="o.value" :disabled="o.disabled">{{ o.text }}</option>
    </select>
</template>
