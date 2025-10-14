<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    description: '',
    points: 10,
    photo_requirements: 'none',
});

const submit = () => {
    form.post(route('admin.templates.store'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Chore Template" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Create Chore Template
                </h2>
                <Link
                    :href="route('admin.templates.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Templates
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Template Name" />
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

                            <div>
                                <InputLabel for="photo_requirements" value="Photo Requirements" />
                                <select
                                    id="photo_requirements"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.photo_requirements"
                                    required
                                >
                                    <option value="none">No photos required</option>
                                    <option value="after">After photo required</option>
                                    <option value="both">Before and after photos required</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">What photos should be taken when completing this chore?</p>
                                <InputError class="mt-2" :message="form.errors.photo_requirements" />
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('admin.templates.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create Template
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
