<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    templates: Object,
});

const formatPoints = (points) => `${points} pts`;
const getPhotoRequirementsText = (requirement) => {
    const texts = {
        'none': 'No photos',
        'after': 'After photo',
        'both': 'Before & after'
    };
    return texts[requirement] || 'No photos';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Chore Templates" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Chore Templates
                </h2>
                <Link
                    :href="route('admin.templates.create')"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                >
                    Create Template
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Empty State -->
                <div v-if="templates.data.length === 0" class="text-center py-12">
                    <div class="mx-auto h-12 w-12 text-gray-400">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 48 48" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0120 24c2.759 0 5.208.896 7.287 2.286" />
                        </svg>
                    </div>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No chore templates</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating your first chore template.</p>
                    <div class="mt-6">
                        <Link
                            :href="route('admin.templates.create')"
                            class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                        >
                            Create Template
                        </Link>
                    </div>
                </div>

                <!-- Templates Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="template in templates.data" :key="template.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-medium text-gray-900">{{ template.name }}</h3>
                                <span :class="[
                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                    template.is_active 
                                        ? 'bg-green-100 text-green-800' 
                                        : 'bg-gray-100 text-gray-800'
                                ]">
                                    {{ template.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            
                            <p v-if="template.description" class="text-gray-600 mb-4">{{ template.description }}</p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Points:</span>
                                    <span class="font-medium text-blue-600">{{ formatPoints(template.points) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Photos:</span>
                                    <span class="font-medium">{{ getPhotoRequirementsText(template.photo_requirements) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Active chores:</span>
                                    <span class="font-medium">{{ template.chores?.length || 0 }}</span>
                                </div>
                            </div>

                            <div class="flex space-x-2">
                                <Link
                                    :href="route('admin.templates.show', template.id)"
                                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold py-2 px-4 rounded text-center text-sm transition-colors"
                                >
                                    View
                                </Link>
                                <Link
                                    :href="route('admin.templates.edit', template.id)"
                                    class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded text-center text-sm transition-colors"
                                >
                                    Edit
                                </Link>
                                <DangerButton
                                    class="flex-1 text-sm"
                                    @click="deleteTemplate(template)"
                                >
                                    Delete
                                </DangerButton>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="templates.links" class="mt-6">
                    <nav class="flex items-center justify-between">
                        <div class="flex items-center">
                            <p class="text-sm text-gray-700">
                                Showing {{ templates.from }} to {{ templates.to }} of {{ templates.total }} results
                            </p>
                        </div>
                        <div class="flex space-x-1">
                            <template v-for="link in templates.links" :key="link.label">
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-2 text-sm border rounded-md',
                                        link.active 
                                            ? 'bg-blue-500 text-white border-blue-500' 
                                            : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
                                    ]"
                                />
                                <span
                                    v-else
                                    v-html="link.label"
                                    class="px-3 py-2 text-sm text-gray-500 border border-gray-300 rounded-md bg-gray-100"
                                />
                            </template>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
