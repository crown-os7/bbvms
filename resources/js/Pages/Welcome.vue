<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Home" />
  <GuestLayout>
    <!-- CONTENT -->
    <main class="flex items-center justify-center flex-1 py-10">
      <div
        class="max-w-2xl text-center bg-white p-8 rounded-2xl shadow-lg space-y-4"
      >
        <h1 class="text-4xl font-bold text-gray-900">Welcome To BB VMS</h1>
        <p class="text-lg text-gray-600">
          Bima Bisalloy Visitor Management System (VMS) helps companies
          record guest arrivals, manage visit schedules, and
          monitor entry and exit activities in real time. This system aims to
          improve security, efficiency, and guest experience during
          visits.
        </p>
      </div>
    </main>
  </GuestLayout>
</template>
