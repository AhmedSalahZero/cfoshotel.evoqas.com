<!--
  CFO Hotels — FormField (label + control + hint + error, the same everywhere)
  Location: resources/js/Components/Form/FormField.vue
  <FormField :label="a translated text" for="s-name" required :hint="..." :error="form.errors.study_name">
      <input id="s-name" class="inp" ...>
  </FormField>
  The control goes in the slot. A red star marks a required box, "optional" marks one that may stay empty.
  The error is read out by screen readers (role="alert").
-->
<script setup>
import { useI18n } from '@/lang/i18n';

defineProps({
    label: { type: String, default: '' },
    for: { type: String, default: undefined },
    required: { type: Boolean, default: false },
    optional: { type: Boolean, default: false },
    hint: { type: String, default: '' },
    error: { type: String, default: '' },
});

const { t } = useI18n();
</script>

<template>
    <div class="fx">
        <label v-if="label" :for="$props.for">
            <span>{{ label }}</span>
            <span v-if="required" class="fx-req" aria-hidden="true">*</span>
            <span v-if="optional" class="fx-opt">{{ t('studies.optional') }}</span>
        </label>
        <slot />
        <p v-if="error" class="fx-err" role="alert">{{ error }}</p>
        <p v-else-if="hint" class="fx-hint">{{ hint }}</p>
    </div>
</template>
