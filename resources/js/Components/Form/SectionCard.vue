<!--
  CFO Hotels — SectionCard (one numbered section of a long form)
  Location: resources/js/Components/Form/SectionCard.vue
  <SectionCard id="sec-main" :number="1" :title="..." :text="..."> fields </SectionCard>
  The id is what the side navigator (SectionNav) scrolls to. Extra things for the header (a switch) go in the "actions" slot.
-->
<script setup>
defineProps({
    id: { type: String, required: true },
    number: { type: Number, required: true },
    title: { type: String, required: true },
    text: { type: String, default: '' },
});
</script>

<template>
    <section :id="id" class="sf-sec" :aria-labelledby="`${id}-h`">
        <header>
            <span class="sf-sec-no" aria-hidden="true">{{ number }}</span>
            <div>
                <h3 :id="`${id}-h`">{{ title }}</h3>
                <p v-if="text">{{ text }}</p>
            </div>
            <slot name="actions" />
        </header>
        <div class="sf-body"><slot /></div>
    </section>
</template>
