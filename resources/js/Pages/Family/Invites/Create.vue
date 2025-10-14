<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    email: '',
    role: 'member',
    expires_at: '',
});

const submit = () => {
    form.post(route('family.invites.store'), {
        onFinish: () => form.reset('password'),
    });
};

const getDefaultExpiryDate = () => {
    const date = new Date();
    date.setDate(date.getDate() + 7); // 7 days from now
    return date.toISOString().split('T')[0];
};

// Set default expiry date
form.expires_at = getDefaultExpiryDate();
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Family Invite" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Create Family Invite
                </h2>
                <Link
                    :href="route('family.invites.index')"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded"
                >
                    Back to Invites
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Invite New Member</h3>
                            <p class="text-sm text-gray-600">
                                Create an invite code that allows someone to join your family. You can specify their role and when the invite expires.
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- Email (Optional) -->
                            <div>
                                <InputLabel for="email" value="Email Address (Optional)" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    placeholder="Enter email address (optional)"
                                />
                                <p class="mt-1 text-sm text-gray-500">If provided, the invite will be associated with this email address</p>
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <!-- Role -->
                            <div>
                                <InputLabel for="role" value="Role" />
                                <select
                                    id="role"
                                    class="mt-1 block w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-md shadow-sm"
                                    v-model="form.role"
                                    required
                                >
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </select>
                                <p class="mt-1 text-sm text-gray-500">
                                    <span class="font-medium">Member:</span> Can complete chores, redeem items, contribute to goals<br>
                                    <span class="font-medium">Admin:</span> Can manage chores, templates, shop items, and family settings
                                </p>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>

                            <!-- Expiry Date -->
                            <div>
                                <InputLabel for="expires_at" value="Expires On" />
                                <TextInput
                                    id="expires_at"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.expires_at"
                                    :min="new Date().toISOString().split('T')[0]"
                                    required
                                />
                                <p class="mt-1 text-sm text-gray-500">The invite will expire on this date and can no longer be used</p>
                                <InputError class="mt-2" :message="form.errors.expires_at" />
                            </div>

                            <!-- Information Box -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">
                                            How to share the invite
                                        </h3>
                                        <div class="mt-2 text-sm text-blue-700">
                                            <p>After creating the invite, you'll receive a unique invite code that you can share with the person you want to invite. They can use this code when registering for an account or in their account settings to join your family.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end space-x-4">
                                <Link
                                    :href="route('family.invites.index')"
                                    class="text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create Invite
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
