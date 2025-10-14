<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    invite: Object,
});

const registrationType = ref(props.invite ? 'join' : 'create');
const inviteCodeError = ref('');

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    family_name: '',
    invite_code: props.invite?.invite_code || '',
});

const isJoiningFamily = computed(() => registrationType.value === 'join');
const isCreatingFamily = computed(() => registrationType.value === 'create');

const checkInviteCode = async () => {
    if (!form.invite_code || form.invite_code.length < 5) {
        inviteCodeError.value = '';
        return;
    }

    try {
        const response = await fetch(route('invites.check'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ invite_code: form.invite_code })
        });

        const data = await response.json();
        
        if (data.valid) {
            inviteCodeError.value = '';
        } else {
            inviteCodeError.value = data.message;
        }
    } catch (error) {
        inviteCodeError.value = 'Error checking invite code';
    }
};

watch(() => form.invite_code, checkInviteCode);

const submit = () => {
    if (isJoiningFamily.value && inviteCodeError.value) {
        return;
    }
    
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Register" />

        <!-- Registration Type Selection -->
        <div v-if="!props.invite" class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">How would you like to get started?</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                    @click="registrationType = 'create'"
                    :class="[
                        'p-4 border-2 rounded-lg text-left transition-colors',
                        isCreatingFamily 
                            ? 'border-blue-500 bg-blue-50' 
                            : 'border-gray-300 hover:border-gray-400'
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <div :class="[
                            'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                            isCreatingFamily 
                                ? 'border-blue-500 bg-blue-500' 
                                : 'border-gray-300'
                        ]">
                            <div v-if="isCreatingFamily" class="w-2 h-2 rounded-full bg-white"></div>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Create New Family</h3>
                            <p class="text-sm text-gray-600">Start a new family and invite members</p>
                        </div>
                    </div>
                </button>
                
                <button
                    @click="registrationType = 'join'"
                    :class="[
                        'p-4 border-2 rounded-lg text-left transition-colors',
                        isJoiningFamily 
                            ? 'border-blue-500 bg-blue-50' 
                            : 'border-gray-300 hover:border-gray-400'
                    ]"
                >
                    <div class="flex items-center space-x-3">
                        <div :class="[
                            'w-4 h-4 rounded-full border-2 flex items-center justify-center',
                            isJoiningFamily 
                                ? 'border-blue-500 bg-blue-500' 
                                : 'border-gray-300'
                        ]">
                            <div v-if="isJoiningFamily" class="w-2 h-2 rounded-full bg-white"></div>
                        </div>
                        <div>
                            <h3 class="font-medium text-gray-900">Join Existing Family</h3>
                            <p class="text-sm text-gray-600">Use an invite code to join a family</p>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <!-- Invite Info (when coming from invite link) -->
        <div v-if="props.invite" class="mb-6 rounded-lg bg-blue-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">
                        Joining {{ invite.family.name }}
                    </h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>You've been invited to join this family. Complete your registration below.</p>
                    </div>
                </div>
            </div>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Family Name (only show if creating new family) -->
            <div v-if="isCreatingFamily" class="mt-4">
                <InputLabel for="family_name" value="Family Name" />

                <TextInput
                    id="family_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.family_name"
                    required
                    placeholder="Enter your family name"
                />

                <InputError class="mt-2" :message="form.errors.family_name" />
            </div>

            <!-- Invite Code (only show if joining existing family) -->
            <div v-if="isJoiningFamily" class="mt-4">
                <InputLabel for="invite_code" value="Invite Code" />

                <TextInput
                    id="invite_code"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.invite_code"
                    required
                    placeholder="Enter the invite code you received"
                    :class="{ 'border-red-500': inviteCodeError }"
                />

                <div v-if="inviteCodeError" class="mt-2 text-sm text-red-600">
                    {{ inviteCodeError }}
                </div>
                <InputError class="mt-2" :message="form.errors.invite_code" />
                
                <p class="mt-1 text-sm text-gray-600">
                    Ask a family member for the invite code to join their family.
                </p>
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>


            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ isJoiningFamily ? 'Join Family' : 'Create Account' }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
