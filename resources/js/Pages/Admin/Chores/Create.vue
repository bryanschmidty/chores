<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';

const props = defineProps({
    templates: Array,
    familyMembers: Array,
});

const form = useForm({
    name: '',
    description: '',
    template_id: null,
    assigned_to: '',
    points: 10,
    recurrence_type: 'none',
    recurrence_interval: null,
    next_due_date: '',
    requires_verification: false,
});

const selectedTemplate = computed(() => {
    return props.templates.find(t => t.id == form.template_id);
});

const onTemplateChange = () => {
    if (selectedTemplate.value) {
        form.name = selectedTemplate.value.name;
        form.description = selectedTemplate.value.description || '';
        form.points = selectedTemplate.value.points;
    }
};

const submit = () => {
    form.post(route('admin.chores.store'), {
        onFinish: () => form.reset('password'),
    });
};

const getRecurrenceText = (type) => {
    const texts = {
        'none': 'One-time',
        'daily': 'Daily',
        'weekly': 'Weekly',
        'monthly': 'Monthly',
        'custom': 'Custom interval'
    };
    return texts[type] || 'One-time';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Chore" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Create New Chore
                </h2>
                <Link
                    :href="route('admin.chores.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Chores
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Template Selection -->
                            <div v-if="templates.length > 0">
                                <InputLabel for="template_id" value="Use Template (Optional)" />
                                <select
                                    id="template_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.template_id"
                                    @change="onTemplateChange"
                                >
                                    <option value="">Create from scratch</option>
                                    <option v-for="template in templates" :key="template.id" :value="template.id">
                                        {{ template.name }} ({{ template.points }} pts)
                                    </option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">Select a template to auto-fill some fields</p>
                                <InputError class="mt-2" :message="form.errors.template_id" />
                            </div>

                            <!-- Chore Name -->
                            <div>
                                <InputLabel for="name" value="Chore Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    placeholder="e.g., Clean Bathroom"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Description -->
                            <div>
                                <InputLabel for="description" value="Description (Optional)" />
                                <textarea
                                    id="description"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Describe what needs to be done..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Assigned To -->
                            <div>
                                <InputLabel for="assigned_to" value="Assign To" />
                                <select
                                    id="assigned_to"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.assigned_to"
                                    required
                                >
                                    <option value="">Select family member</option>
                                    <option v-for="member in familyMembers" :key="member.id" :value="member.id">
                                        {{ member.name }} ({{ member.role }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.assigned_to" />
                            </div>

                            <!-- Points -->
                            <div>
                                <InputLabel for="points" value="Points" />
                                <TextInput
                                    id="points"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.points"
                                    min="1"
                                    max="1000"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">How many points should this chore be worth?</p>
                                <InputError class="mt-2" :message="form.errors.points" />
                            </div>

                            <!-- Due Date -->
                            <div>
                                <InputLabel for="next_due_date" value="Due Date" />
                                <TextInput
                                    id="next_due_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.next_due_date"
                                    :min="new Date().toISOString().split('T')[0]"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.next_due_date" />
                            </div>

                            <!-- Recurrence Type -->
                            <div>
                                <InputLabel for="recurrence_type" value="Recurrence" />
                                <select
                                    id="recurrence_type"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.recurrence_type"
                                    required
                                >
                                    <option value="none">One-time</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="custom">Custom interval</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.recurrence_type" />
                            </div>

                            <!-- Custom Interval -->
                            <div v-if="form.recurrence_type === 'custom'">
                                <InputLabel for="recurrence_interval" value="Custom Interval (Days)" />
                                <TextInput
                                    id="recurrence_interval"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model="form.recurrence_interval"
                                    min="1"
                                    max="365"
                                    placeholder="e.g., 3 for every 3 days"
                                />
                                <InputError class="mt-2" :message="form.errors.recurrence_interval" />
                            </div>

                            <!-- Verification Required -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="requires_verification"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        v-model="form.requires_verification"
                                    />
                                    <label for="requires_verification" class="ml-2 text-sm text-gray-600">
                                        Requires verification by admin
                                    </label>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">If checked, an admin must verify completion before points are awarded</p>
                                <InputError class="mt-2" :message="form.errors.requires_verification" />
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('admin.chores.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create Chore
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
