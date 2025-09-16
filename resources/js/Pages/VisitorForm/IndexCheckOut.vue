<script setup>
import { ref, onMounted, computed } from "vue";
import { Keyboard } from "lucide-vue-next";
import { router, usePage } from "@inertiajs/vue3";
import { QrcodeStream } from "vue-qrcode-reader";

import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);

const scanError = ref("");

onMounted(() => {
  scanError.value = "";
});

const onDetect = (detectedCodes) => {
  if (!detectedCodes.length) return;

  let scanned = detectedCodes[0].rawValue;

  // ✅ Jika QR code berisi URL, ambil hanya parameter "ref"
  try {
    if (scanned.includes("ref=")) {
      const url = new URL(scanned, window.location.origin);
      scanned = url.searchParams.get("ref") || scanned;
    }
  } catch (e) {
    console.warn("Bukan URL, gunakan nilai mentah:", scanned);
  }

  router.visit(`/check-out?ref=${encodeURIComponent(scanned)}`);
};

const onError = (err) => {
  console.error("QR Scan error:", err);
  scanError.value = "Gagal membaca barcode, coba lagi.";
};
</script>

<template>
  <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
    <div class="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <div class="bg-white shadow-lg rounded-2xl p-8 w-full max-w-lg text-center">
        <h1 class="text-2xl font-bold mb-4">
          {{ isAuthenticated ? "Visitor Check-Out" : "Guest Check-Out" }}
        </h1>

        <!-- Kamera + Overlay Kotak -->
        <div class="relative w-full rounded-xl overflow-hidden border border-gray-300 mb-4">
          <qrcode-stream
            :constraints="{ facingMode: 'user' }"
            @detect="onDetect"
            @error="onError"
            class="w-full h-72 object-cover"
          ></qrcode-stream>

          <!-- ✅ Overlay 4 sudut -->
          <div
            class="absolute inset-0 flex items-center justify-center pointer-events-none"
          >
            <div class="relative w-48 h-48">
              <!-- Sudut kiri atas -->
              <div class="absolute top-0 left-0 w-6 h-6 border-t-4 border-l-4 border-blue-500 rounded-tl-lg"></div>
              <!-- Sudut kanan atas -->
              <div class="absolute top-0 right-0 w-6 h-6 border-t-4 border-r-4 border-blue-500 rounded-tr-lg"></div>
              <!-- Sudut kiri bawah -->
              <div class="absolute bottom-0 left-0 w-6 h-6 border-b-4 border-l-4 border-red-500 rounded-bl-lg"></div>
              <!-- Sudut kanan bawah -->
              <div class="absolute bottom-0 right-0 w-6 h-6 border-b-4 border-r-4 border-red-500 rounded-br-lg"></div>
            </div>
          </div>
        </div>

        <p v-if="scanError" class="text-red-600 mb-4 text-sm">
          {{ scanError }}
        </p>

        <!-- Tombol Manual Check-Out -->
        <button
          @click="router.visit('/check-out')"
          class="w-full flex items-center justify-center gap-2 bg-gray-600 hover:bg-gray-700 text-white py-3 px-4 rounded-xl shadow-md transition"
        >
          <Keyboard class="w-6 h-6" />
          <span>Manual Check-Out</span>
        </button>
      </div>
    </div>
  </component>
</template>
