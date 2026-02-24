<script setup>
import { computed } from 'vue'

const props = defineProps({
    modelValue: String,
    disabled: {
        type: Boolean,
        default: false
    },
    required: {
        type: Boolean,
        default: false
    },
    class: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['update:modelValue'])

const model = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})
</script>

<template>
    <select
        v-model="model"
        :disabled="disabled"
        :required="required"
        :class="[
            'block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm',
            disabled ? 'bg-gray-50' : '',
            props.class
        ]"
    >
        <slot />
    </select>
</template>










