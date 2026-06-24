<script setup>
defineProps({
    title: String,
    value: [String, Number],
    icon: String,
    colorClass: {
        type: String,
        default: 'text-purple-600'
    },
    bgClass: {
        type: String,
        default: 'bg-purple-100'
    }
});
</script>

<template>
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 transition hover:shadow-md">
        <div class="flex items-center justify-between mb-4">
            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', bgClass, colorClass]">
                <i :class="['pi', icon, 'text-lg']"></i>
            </div>
        </div>
        <h3 class="text-slate-500 text-sm font-medium">{{ title }}</h3>
        <p class="text-2xl font-bold text-slate-800">{{ value }}</p>
    </div>
</template>
