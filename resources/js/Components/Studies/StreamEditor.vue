<!--
  CFO Hotels — StreamEditor (one revenue stream of the study form)
  Location: resources/js/Components/Studies/StreamEditor.vue
  <StreamEditor stream-key="rooms" :stream="form.streams.rooms" :types="options.types.rooms" :errors="form.errors" icon="bed" :title="..." />
  A stream is described either as ONE total ("120 rooms in the hotel") or BY TYPE (a row per type, each type once).
  The stream object is edited in place: { on, mode: 'total' | 'types', rows: [{ type_id, count, value }] }.
  Rooms and food and beverage are always part of a study; the other streams have an on / off switch.
-->
<script setup>
import { computed } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import ChoiceCards from '@/Components/Form/ChoiceCards.vue';
import FormField from '@/Components/Form/FormField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import SelectField from '@/Components/Form/SelectField.vue';
import SwitchField from '@/Components/Form/SwitchField.vue';
import { formatNumber } from '@/Utils/format';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    streamKey: { type: String, required: true },
    stream: { type: Object, required: true },
    types: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    icon: { type: String, required: true },
    title: { type: String, required: true },
    optional: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    // false for other revenue, which has a count only
    hasValue: { type: Boolean, default: true },
    valueDecimals: { type: Number, default: 0 },
    valueMax: { type: Number, default: 100000 },
});

const { t } = useI18n();
const key = (suffix) => `streams.${props.streamKey}.${suffix}`;
const err = (i, field) => props.errors[key(`rows.${i}.${field}`)] ?? '';

const isTotal = computed(() => props.stream.mode === 'total');
const rowsShown = computed(() => (isTotal.value ? props.stream.rows.slice(0, 1) : props.stream.rows));
const sum = computed(() => rowsShown.value.reduce((s, r) => s + (Number(r.count) || 0), 0));
const canAdd = computed(() => !props.disabled && props.stream.rows.length < props.types.length);

const modes = computed(() => [
    { value: 'total', title: t(`studies.streams.${props.streamKey}.total`), text: t(`studies.streams.${props.streamKey}.totalText`) },
    { value: 'types', title: t('studies.byType'), text: t(`studies.streams.${props.streamKey}.typesText`) },
]);

function optionsFor(row) {
    const used = new Set(props.stream.rows.filter((r) => r !== row).map((r) => r.type_id).filter(Boolean));

    return props.types.map((type) => ({ value: type.id, text: type.title, disabled: used.has(type.id) }));
}

function addRow() {
    props.stream.rows.push({ type_id: null, count: null, value: null });
}

function removeRow(index) {
    if (props.stream.rows.length > 1) props.stream.rows.splice(index, 1);
}
</script>

<template>
    <div class="sf-stream" :class="{ off: optional && !stream.on }" :data-stream="streamKey">
        <header>
            <span class="sf-stream-ico" aria-hidden="true"><AppIcon :name="icon" :size="17" /></span>
            <h4>{{ title }}</h4>
            <span v-if="(!optional || stream.on) && sum > 0" class="sf-stream-sum">{{ t(`studies.streams.${streamKey}.sum`, { count: formatNumber(sum) }) }}</span>
            <SwitchField v-if="optional" v-model="stream.on" :disabled="disabled">{{ stream.on ? t('studies.on') : t('studies.off') }}</SwitchField>
        </header>

        <p v-if="optional && !stream.on" class="sf-off-note">{{ t('studies.streamOff') }}</p>

        <div class="sf-stream-body">
            <ChoiceCards v-model="stream.mode" :options="modes" :legend="title" :disabled="disabled" />

            <div class="sf-rows">
                <div v-for="(row, i) in rowsShown" :key="i" class="sf-row" :class="{ total: isTotal && hasValue, nov: !hasValue && !isTotal, one: isTotal && !hasValue }" :data-row="i">
                    <FormField v-if="!isTotal" :label="t(`studies.streams.${streamKey}.type`)" :for="`${streamKey}-t${i}`" required :error="err(i, 'type_id')">
                        <SelectField :id="`${streamKey}-t${i}`" v-model="row.type_id" :options="optionsFor(row)" :placeholder="t('studies.choose')" :invalid="!!err(i, 'type_id')" :disabled="disabled" />
                    </FormField>
                    <FormField :label="t(`studies.streams.${streamKey}.count`)" :for="`${streamKey}-c${i}`" required :error="err(i, 'count')">
                        <NumberField :id="`${streamKey}-c${i}`" v-model="row.count" :decimals="0" :min="1" :max="100000" :invalid="!!err(i, 'count')" :disabled="disabled" />
                    </FormField>
                    <FormField v-if="hasValue" :label="t(`studies.streams.${streamKey}.value`)" :for="`${streamKey}-v${i}`" required :error="err(i, 'value')">
                        <NumberField :id="`${streamKey}-v${i}`" v-model="row.value" :decimals="valueDecimals" :min="1" :max="valueMax" :invalid="!!err(i, 'value')" :disabled="disabled" />
                    </FormField>
                    <button v-if="!isTotal" type="button" class="sf-row-x" :disabled="disabled || stream.rows.length < 2" :aria-label="t('studies.removeRow')" :title="t('studies.removeRow')" @click="removeRow(i)">
                        <AppIcon name="trash" :size="16" />
                    </button>
                </div>
            </div>

            <p v-if="errors[key('rows')]" class="fx-err" role="alert">{{ errors[key('rows')] }}</p>

            <div v-if="!isTotal" class="row">
                <button type="button" class="btn btn-line sm" :disabled="!canAdd" @click="addRow"><AppIcon name="plus" :size="14" />{{ t('studies.addType') }}</button>
                <span v-if="!canAdd && !disabled" class="mute small">{{ t('studies.allTypesUsed') }}</span>
            </div>
        </div>
    </div>
</template>
