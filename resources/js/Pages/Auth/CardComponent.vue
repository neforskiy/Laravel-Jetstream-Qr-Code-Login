<script setup>
defineProps({
    maxWidth: {
        type: String,
        default: 'md', // sm | md | lg | xl
    },
})

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
}
</script>

<template>
    <div class="min-h-screen flex flex-col justify-center items-center px-4 py-6 bg-gray-100 dark:bg-gray-900">
        <div
            class="w-full bg-white dark:bg-gray-800 shadow-xl rounded-lg overflow-hidden"
            :class="maxWidthClass[maxWidth]"
        >
            <div v-if="$slots.title" class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    <slot name="title" />
                </h2>
            </div>

            <div class="px-6 py-4">
                <slot name="body" />
            </div>

            <div v-if="$slots.footer" class="px-6 py-4 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-700">
                <slot name="footer" />
            </div>
        </div>
    </div>
</template>
