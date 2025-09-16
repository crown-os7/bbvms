<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
  canResetPassword: {
    type: Boolean,
  },
  status: {
    type: String,
  },
});

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post(route('login'), {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <GuestLayout>
    <Head title="Login" />

    <div class="w-full max-w-md mx-auto mt-8 bg-white shadow-md rounded-lg p-6">
      <div class="mb-4 text-center">
        <h1 class="text-xl font-bold text-gray-800">Login</h1>
        <!-- <p class="text-gray-600 text-sm">Masuk ke akun anda untuk melanjutkan</p> -->
      </div>

      <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
        {{ status }}
      </div>

      <form @submit.prevent="submit">
        <!-- Email -->
        <div>
          <InputLabel for="email" value="Email" />
          <TextInput
            id="email"
            type="email"
            class="mt-1 block w-full"
            v-model="form.email"
            required
            autofocus
            autocomplete="username"
          />
          <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <!-- Password -->
        <div class="mt-4">
          <InputLabel for="password" value="Password" />
          <TextInput
            id="password"
            type="password"
            class="mt-1 block w-full"
            v-model="form.password"
            required
            autocomplete="current-password"
          />
          <InputError class="mt-2" :message="form.errors.password" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
          <label class="flex items-center">
            <Checkbox name="remember" v-model:checked="form.remember" />
            <span class="ms-2 text-sm text-gray-600">Remember me</span>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex justify-end mt-6">
        <PrimaryButton
            class="ms-4"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
        >
            Log in
        </PrimaryButton>
        </div>

      </form>
    </div>
  </GuestLayout>
</template>

