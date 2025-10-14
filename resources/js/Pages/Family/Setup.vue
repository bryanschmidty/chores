<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    invite_code: '',
});

const submit = () => {
    form.post(route('invites.accept'), {
        onSuccess: () => {
            // Redirect will be handled by the controller
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Family Setup" />

        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Join or Create a Family
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to Family Chores!</h3>
                            <p class="text-gray-600">You need to be part of a family to use the app. You can either join an existing family with an invite code or create a new family.</p>
                        </div>

                        <!-- Join Family Section -->
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-800 mb-4">Join an Existing Family</h4>
                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <InputLabel for="invite_code" value="Family Invite Code" />
                                    <TextInput
                                        id="invite_code"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.invite_code"
                                        placeholder="Enter invite code"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.invite_code" />
                                </div>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Join Family
                                </PrimaryButton>
                            </form>
                        </div>

                        <!-- Divider -->
                        <div class="relative mb-8">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-300" />
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-2 bg-white text-gray-500">Or</span>
                            </div>
                        </div>

                        <!-- Create Family Section -->
                        <div>
                            <h4 class="text-md font-medium text-gray-800 mb-4">Create a New Family</h4>
                            <p class="text-gray-600 mb-4">Start your own family and invite others to join.</p>
                            <Link
                                :href="route('register')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create New Family
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
