<script setup>
const props = defineProps({
    modelValue: Number,
    max: {
        type: Number,
        default: 5
    },
    readonly: Boolean
});

const emit = defineEmits(['update:modelValue']);

const updateRating = (n) => {
    if (props.readonly) return;
    emit('update:modelValue', n);
};
</script>

<template>
    <div class="flex gap-1">
        <button v-for="n in max" :key="n" 
            @click="updateRating(n)" 
            :disabled="readonly"
            :class="[
                modelValue >= n ? 'text-purple-600' : 'text-slate-200',
                readonly ? 'cursor-default' : 'hover:scale-110 transition'
            ]" 
            class="text-xl">
            <i class="pi" :class="modelValue >= n ? 'pi-star-fill' : 'pi-star'"></i>
        </button>
    </div>
</template>
