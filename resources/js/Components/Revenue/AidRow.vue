<!--
  CFO Hotels — AidRow (a reading aid under a row of yearly boxes)
  Location: resources/js/Components/Revenue/AidRow.vue
  <YearGrid ...><template #foot><AidRow :label="..." :years="years" :values="aids.guests" /></template></YearGrid>
  A line of worked-out figures in the same table as the boxes, so every figure sits under its year. Nothing here can be typed in.
-->
<script setup>
import { money } from '@/Utils/adr';

defineProps({
    label: { type: String, required: true },
    years: { type: Array, required: true },
    values: { type: Object, default: () => ({}) },
    decimals: { type: Number, default: 0 },
});
</script>

<template>
    <tr class="rv-aid" data-aid>
        <th scope="row" class="rv-rowhead">{{ label }}</th>
        <td v-for="y in years" :key="y.index" class="rv-num" dir="ltr">{{ money(values?.[y.index] ?? 0, decimals) }}</td>
    </tr>
</template>
