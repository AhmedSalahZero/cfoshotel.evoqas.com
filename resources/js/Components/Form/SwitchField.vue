<!--
  CFO Hotels — SwitchField (on / off)
  Location: resources/js/Components/Form/SwitchField.vue
  <SwitchField v-model="form.channels.on">{{ a translated text }}</SwitchField>
  The text in the slot is the label. Space or Enter flips it; screen readers hear "switch, on / off".
-->
<script setup>
defineProps({
    modelValue: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <button type="button" class="fx-switch" role="switch" :aria-checked="modelValue" :disabled="disabled" @click="emit('update:modelValue', !modelValue)">
        <span class="fx-switch-track" />
        <span><slot /></span>
    </button>
</template>
