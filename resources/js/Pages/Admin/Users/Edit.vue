<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};

const getRoleColor = (role) => {
    const colors = {
        'super-admin': 'bg-red-100 text-red-800',
        'admin': 'bg-blue-100 text-blue-800',
        'member': 'bg-gray-100 text-gray-800',
    };
    return colors[role] || 'bg-gray-100 text-gray-800';
};

const getRoleText = (role) => {
    const texts = {
        'super-admin': 'Super Admin',
        'admin': 'Admin',
        'member': 'Member',
    };
    return texts[role] || 'Unknown';
};

const formatLastLogin = (lastLoggedIn) => {
    if (!lastLoggedIn) {
        return 'Never';
    }
    
    const now = new Date();
    const loginDate = new Date(lastLoggedIn);
    const diffInSeconds = Math.floor((now - loginDate) / 1000);
    
    if (diffInSeconds < 60) {
        return 'Just now';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} day${days !== 1 ? 's' : ''} ago`;
    } else {
        return loginDate.toLocaleDateString();
    }
};

const getLastLoginClass = (lastLoggedIn) => {
    if (!lastLoggedIn) {
        return 'text-gray-400 italic';
    }
    
    const now = new Date();
    const loginDate = new Date(lastLoggedIn);
    const diffInHours = Math.floor((now - loginDate) / (1000 * 60 * 60));
    
    if (diffInHours < 24) {
        return 'text-green-600 font-medium';
    } else if (diffInHours < 168) { // 7 days
        return 'text-yellow-600';
    } else {
        return 'text-red-600';
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit User" />

        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Edit User
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
                    <div class="p-6">
                        <!-- User Info Display -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">User Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-500">Current Role:</span>
                                    <span :class="[
                                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ml-2',
                                        getRoleColor(user.role)
                                    ]">
                                        {{ getRoleText(user.role) }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500">Points Balance:</span>
                                    <span class="ml-1 text-blue-600 font-medium">{{ user.points_balance || 0 }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500">Joined:</span>
                                    <span class="ml-1">{{ new Date(user.created_at).toLocaleDateString() }}</span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500">Email Verified:</span>
                                    <span class="ml-1" :class="user.email_verified_at ? 'text-green-600' : 'text-red-600'">
                                        {{ user.email_verified_at ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-500">Last Login:</span>
                                    <span class="ml-1" :class="getLastLoginClass(user.last_logged_in)">
                                        {{ formatLastLogin(user.last_logged_in) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Name" />
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

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="role" value="Role" />
                                <SelectInput
                                    id="role"
                                    class="mt-1 block w-full"
                                    v-model="form.role"
                                    required
                                >
                                    <option value="member">Member</option>
                                    <option value="admin">Admin</option>
                                </SelectInput>
                                <p class="mt-1 text-sm text-gray-500">
                                    <span class="font-medium">Member:</span> Can complete chores, redeem items, contribute to goals<br>
                                    <span class="font-medium">Admin:</span> Can manage chores, templates, shop items, and family settings
                                </p>
                                <InputError class="mt-2" :message="form.errors.role" />
                            </div>

                            <div class="flex items-center justify-end space-x-3">
                                <Link
                                    :href="route('admin.family.index')"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Update User
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
