<script setup>
import { Head } from "@inertiajs/vue3";

const props = defineProps({
  booking: Object,
  visitor: Object,
});
</script>

<template>
  <Head title="Visitor Detail" />

  <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow space-y-6">
    <h2 class="text-2xl font-bold text-gray-800">Visitor Details</h2>

    <!-- Visitor Card -->
    <div class="bg-gray-50 rounded-lg p-6 shadow-inner">
      <h3 class="text-lg font-semibold mb-3 text-gray-700 text-center">Visitor Information</h3>

      <!-- Foto Visitor -->
      <div class="flex justify-center mb-4">
        <img
          v-if="visitor.photo"
          :src="visitor.photo"
          alt="Visitor Photo"
          class="w-28 h-28 rounded-lg object-cover border shadow"
        />
        <div v-else class="w-28 h-28 rounded-lg bg-gray-200 flex items-center justify-center text-gray-500 text-xs">
          No Photo
        </div>
      </div>

      <!-- Detail Visitor -->
      <div class="space-y-2 text-sm text-gray-700">
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Name</span>
          <span>{{ visitor.name }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Company</span>
          <span>{{ visitor.company }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Position</span>
          <span>{{ visitor.position }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Email</span>
          <span>{{ visitor.email ?? '-' }}</span>
        </div>
        <div class="flex justify-between">
          <span class="font-medium">Visitor Status</span>
          <span class="px-2 py-0.5 rounded text-white text-xs"
            :class="visitor.status === 'approved' ? 'bg-green-500' : 'bg-gray-400'">
            {{ visitor.status ?? '-' }}
          </span>
        </div>
      </div>
    </div>

    <!-- Meeting Card -->
    <div class="bg-gray-50 rounded-lg p-6 shadow-inner">
      <h3 class="text-lg font-semibold mb-3 text-gray-700">Meeting Details</h3>
      <div class="space-y-2 text-sm text-gray-700">
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Room</span>
          <span>{{ booking.room?.name }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Meeting With</span>
          <span>{{ booking.meeting_with }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Date</span>
          <span>{{ booking.date }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Start Time</span>
          <span>{{ booking.start_time }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">End Time</span>
          <span>{{ booking.end_time }}</span>
        </div>
        <div class="flex justify-between border-b pb-1">
          <span class="font-medium">Purpose</span>
          <span>{{ booking.purpose }}</span>
        </div>
        <div class="flex justify-between">
          <span class="font-medium">Meeting Status</span>
          <span class="px-2 py-0.5 rounded text-white text-xs"
            :class="booking.status === 'open' ? 'bg-green-500' : 'bg-red-500'">
            {{ booking.status }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
