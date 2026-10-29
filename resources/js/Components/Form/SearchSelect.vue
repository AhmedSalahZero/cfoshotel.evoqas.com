<!--
  CFO Hotels — SearchSelect (a drop-down you can type into)
  Location: resources/js/Components/Form/SearchSelect.vue
  <SearchSelect id="s-country" v-model="form.country_id" :options="countries" :placeholder="a translated text" clearable />
  options = [{ value, text }]. Type to narrow the list, arrows + Enter to choose, Escape to close.
  For long lists (countries, states) where scrolling a plain drop-down is slow.
-->
<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useId, watch } from 'vue';
import AppIcon from '@/Components/AppIcon.vue';
import { useI18n } from '@/lang/i18n';

const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    options: { type: Array, required: true },
    id: { type: String, default: undefined },
    placeholder: { type: String, default: '' },
    invalid: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);

const { t } = useI18n();
const uid = useId();
const root = ref(null);
const open = ref(false);
const query = ref('');
const active = ref(0);

const selected = computed(() => props.options.find((o) => o.value === props.modelValue) ?? null);
const shown = computed(() => (open.value ? query.value : (selected.value?.text ?? '')));
const matches = computed(() => {
    const q = query.value.trim().toLowerCase();

    return q === '' ? props.options : props.options.filter((o) => String(o.text).toLowerCase().includes(q));
});

watch(matches, () => { active.value = 0; });

function openList() {
    if (props.disabled) return;
    open.value = true;
    query.value = '';
    const at = matches.value.findIndex((o) => o.value === props.modelValue);
    active.value = at < 0 ? 0 : at;
    nextTick(scrollToActive);
}

function closeList() {
    open.value = false;
    query.value = '';
}

function choose(option) {
    emit('update:modelValue', option.value);
    closeList();
}

function clear() {
    emit('update:modelValue', null);
    closeList();
}

function scrollToActive() {
    root.value?.querySelector('li.on')?.scrollIntoView?.({ block: 'nearest' });
}

function onKey(event) {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!open.value) return openList();
        const step = event.key === 'ArrowDown' ? 1 : -1;
        active.value = (active.value + step + Math.max(matches.value.length, 1)) % Math.max(matches.value.length, 1);
        nextTick(scrollToActive);
    } else if (event.key === 'Enter' && open.value) {
        event.preventDefault();
        if (matches.value[active.value]) choose(matches.value[active.value]);
    } else if (event.key === 'Escape' && open.value) {
        event.preventDefault();
        closeList();
    } else if (event.key === 'Tab') {
        closeList();
    }
}

function outside(event) {
    if (open.value && root.value && !root.value.contains(event.target)) closeList();
}

onMounted(() => document.addEventListener('mousedown', outside));
onBeforeUnmount(() => document.removeEventListener('mousedown', outside));
</script>

<template>
    <div ref="root" class="fx-combo" :aria-invalid="invalid ? 'true' : undefined">
        <input
            :id="id" class="inp" type="text" role="combobox" autocomplete="off" :disabled="disabled"
            :value="shown" :placeholder="placeholder" :aria-expanded="open" :aria-controls="`${uid}-list`" aria-autocomplete="list"
            :aria-activedescendant="open && matches[active] ? `${uid}-o${active}` : undefined" :aria-invalid="invalid ? 'true' : undefined"
            @focus="openList" @click="openList" @input="query = $event.target.value; open = true" @keydown="onKey"
        >
        <button
            v-if="clearable && selected && !disabled" type="button" class="fx-combo-btn" :aria-label="t('studies.clear')" tabindex="-1"
            @mousedown.prevent="clear"
        ><AppIcon name="x" :size="14" /></button>
        <span v-else class="fx-combo-btn" aria-hidden="true"><AppIcon name="chevron" :size="14" /></span>
        <ul v-if="open" :id="`${uid}-list`" class="fx-list" role="listbox">
            <li
                v-for="(o, i) in matches" :id="`${uid}-o${i}`" :key="o.value" role="option" :class="{ on: i === active }"
                :aria-selected="o.value === modelValue" @mousedown.prevent="choose(o)" @mousemove="active = i"
            >{{ o.text }}</li>
            <li v-if="!matches.length" class="none" role="option" aria-disabled="true" aria-selected="false">{{ t('studies.noOption') }}</li>
        </ul>
    </div>
</template>
