<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    family: Object,
});

const form = useForm({
    name: props.family.name,
    settings: {
        global_verification: props.family.settings?.global_verification || false,
        notification_preferences: props.family.settings?.notification_preferences || {
            email_chore_assigned: true,
            email_chore_completed: true,
            email_chore_verified: true,
            email_overdue_reminder: true,
            browser_chore_assigned: true,
            browser_chore_completed: true,
            browser_chore_verified: true,
            browser_overdue_reminder: true,
        },
    },
});

const submit = () => {
    form.put(route('admin.family.update'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Family Settings" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit Family Settings
                </h2>
                <Link
                    :href="route('admin.family.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Family
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Family Name -->
                            <div>
                                <InputLabel for="name" value="Family Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Global Verification -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="global_verification"
                                        type="checkbox"
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                        v-model="form.settings.global_verification"
                                    />
                                    <label for="global_verification" class="ml-2 text-sm text-gray-600">
                                        Require verification for all chores
                                    </label>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">When enabled, all chores must be verified by an admin before points are awarded</p>
                                <InputError class="mt-2" :message="form.errors['settings.global_verification']" />
                            </div>

                            <!-- Notification Preferences -->
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                                <p class="text-sm text-gray-600 mb-4">Configure default notification settings for all family members</p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <h4 class="text-md font-medium text-gray-800 mb-2">Email Notifications</h4>
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input
                                                    id="email_chore_assigned"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.email_chore_assigned"
                                                />
                                                <label for="email_chore_assigned" class="ml-2 text-sm text-gray-600">
                                                    Chore assigned
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="email_chore_completed"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.email_chore_completed"
                                                />
                                                <label for="email_chore_completed" class="ml-2 text-sm text-gray-600">
                                                    Chore completed (needs verification)
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="email_chore_verified"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.email_chore_verified"
                                                />
                                                <label for="email_chore_verified" class="ml-2 text-sm text-gray-600">
                                                    Chore verified
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="email_overdue_reminder"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.email_overdue_reminder"
                                                />
                                                <label for="email_overdue_reminder" class="ml-2 text-sm text-gray-600">
                                                    Overdue chore reminders
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 class="text-md font-medium text-gray-800 mb-2">Browser Notifications</h4>
                                        <div class="space-y-2">
                                            <div class="flex items-center">
                                                <input
                                                    id="browser_chore_assigned"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.browser_chore_assigned"
                                                />
                                                <label for="browser_chore_assigned" class="ml-2 text-sm text-gray-600">
                                                    Chore assigned
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="browser_chore_completed"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.browser_chore_completed"
                                                />
                                                <label for="browser_chore_completed" class="ml-2 text-sm text-gray-600">
                                                    Chore completed (needs verification)
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="browser_chore_verified"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.browser_chore_verified"
                                                />
                                                <label for="browser_chore_verified" class="ml-2 text-sm text-gray-600">
                                                    Chore verified
                                                </label>
                                            </div>
                                            <div class="flex items-center">
                                                <input
                                                    id="browser_overdue_reminder"
                                                    type="checkbox"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                                    v-model="form.settings.notification_preferences.browser_overdue_reminder"
                                                />
                                                <label for="browser_overdue_reminder" class="ml-2 text-sm text-gray-600">
                                                    Overdue chore reminders
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors['settings.notification_preferences']" />
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('admin.family.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Update Settings
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
