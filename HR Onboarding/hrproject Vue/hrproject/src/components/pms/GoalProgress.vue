<script setup>
defineProps({
    label: String,
    current: Number,
    total: Number,
    subtitle: String,
    color: {
        type: String,
        default: 'bg-purple-600'
    }
});
</script>

<template>
    <div class="group">
        <div class="flex items-center justify-between mb-2">
            <h4 class="font-medium text-slate-700">{{ label }}</h4>
            <span class="text-sm font-semibold" :class="current >= total ? 'text-green-600' : 'text-purple-600'">
                {{ Math.round((current / total) * 100) }}%
            </span>
        </div>
        <div class="w-full bg-slate-100 rounded-full h-2">
            <div :class="['h-2 rounded-full transition-all duration-500', color]" :style="{ width: Math.min(100, (current / total) * 100) + '%' }"></div>
        </div>
        <p v-if="subtitle" class="text-xs text-slate-400 mt-2">{{ subtitle }}</p>
    </div>
</template>
