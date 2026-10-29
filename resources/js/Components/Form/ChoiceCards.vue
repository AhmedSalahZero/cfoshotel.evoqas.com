<!--
  CFO Hotels — ChoiceCards (two or three big radio buttons with an explanation)
  Location: resources/js/Components/Form/ChoiceCards.vue
  <ChoiceCards v-model="stream.mode" :options="[{ value: 'total', title: '...', text: '...' }, ...]" :legend="a translated text" />
-->
<script setup>
import { useId } from 'vue';

defineProps({
    modelValue: { type: String, default: '' },
    options: { type: Array, required: true },
    legend: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const name = useId();
</script>

<template>
    <fieldset class="fx-choice" style="border:0;padding:0;min-width:0" :disabled="disabled">
        <legend v-if="legend" class="vh">{{ legend }}</legend>
        <label v-for="o in options" :key="o.value">
            <input type="radio" :name="name" :value="o.value" :checked="modelValue === o.value" @change="emit('update:modelValue', o.value)">
            <span class="fx-choice-dot" aria-hidden="true" />
            <span><b>{{ o.title }}</b><small v-if="o.text">{{ o.text }}</small></span>
        </label>
    </fieldset>
</template>
