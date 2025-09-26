<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
  <Head title="Home" />
  <GuestLayout>
    <!-- VIDEO BACKGROUND -->
    <div class="relative w-full h-screen overflow-hidden">
      <video
        autoplay
        loop
        muted
        playsinline
        class="absolute top-0 left-0 w-full h-full object-cover"
      >
        <source src="/videos/bg.mp4" type="video/mp4" />
        Browser Anda tidak mendukung video.
      </video>

      <!-- CONTENT -->
      <main
        class="relative flex items-center justify-center h-full bg-black/40"
      >
        <!-- Card Container -->
        <div class="bg-white/30 backdrop-blur-md rounded-2xl p-8 shadow-xl">
          <!-- Container tombol -->
          <div class="flex flex-col space-y-6 items-center">
            <!-- Check-In -->
            <Link
              :href="route('check-in.index')"
              class="px-14 py-8 text-2xl font-bold text-white bg-blue-600/50 shadow-lg transition duration-300 hover:bg-blue-600 w-64 text-center"
              style="border-radius: 0 9999px 9999px 0;"
            >
              Check-In
            </Link>

            <!-- Check-Out -->
            <Link
              :href="route('check-out.index')"
              class="px-14 py-8 text-2xl font-bold text-white bg-red-600/50 shadow-lg transition duration-300 hover:bg-red-600 w-64 text-center"
              style="border-radius: 0 9999px 9999px 0;"
            >
              Check-Out
            </Link>
          </div>
        </div>
      </main>
    </div>
  </GuestLayout>
</template>
