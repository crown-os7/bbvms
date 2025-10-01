<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import QrcodeVue from 'qrcode.vue'
import type { ImageSettings } from 'qrcode.vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const props = defineProps({
  booking: {
    type: Object,
    required: true
  }
})

// ✅ cek apakah user login
const page = usePage()
const isAuthenticated = computed(() => !!page.props.auth?.user)

// ✅ Ambil visitor_id dari URL
const selectedVisitorId = ref<number|null>(null)

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  const vid = params.get("visitor_id")
  if (vid) selectedVisitorId.value = parseInt(vid)
})

const visitorToPrint = computed(() => {
  return props.booking.visitors.find((v: any) => v.id === selectedVisitorId.value) || null
})

const qrValue = computed(() => {
  if (!visitorToPrint.value) return ''
  return `${window.location.origin}/visitor/detail/${props.booking.id}/${visitorToPrint.value.id}`
})

const imageSettings = ref<ImageSettings>({
  src: '/img/logo/logo-bb.png',
  width: 15,
  height: 15,
  excavate: true,
})

onMounted(() => {
  setTimeout(() => {
    window.print()
  }, 500)
})

window.onafterprint = () => {
  // kalau tab dibuka dari window.open → bisa ditutup
  if (window.opener) {
    window.close()
  } else {
    // kalau tab utama → redirect ke check-in
    router.visit("/check-in")
  }
}

// window.onafterprint = () => {
//   window.close()
// }
</script>

<template>
  <Head title="Print Visitor Card" />

  <!-- ✅ pilih layout sesuai status login -->
  <component :is="isAuthenticated ? AuthenticatedLayout : GuestLayout">
    <!-- <template v-if="isAuthenticated" #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Print Visitor Card zzz
      </h2>
    </template> -->

    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg p-2 mt-6">
      <div
        v-if="visitorToPrint"
        class="print-area flex flex-wrap gap-4 justify-center"
      >
        <!-- Kartu Visitor -->
        <div
          class="w-[340px] h-[188px] border rounded-xl overflow-hidden shadow-lg outline outline-1 outline-black"
        >
          <!-- Header Logo -->
          <div
            class="outline outline-1 outline-black pl-2 h-8 flex items-center bg-white"
          >
            <img src="/img/logo/logo.png" alt="Logo" class="h-7" />
          </div>

          <div class="flex">
            <!-- Kiri -->
            <div class="w-1/3 flex flex-col items-center justify-center p-3 pt-4">
              <img
                :src="visitorToPrint.photo ?? '/images/default.png'"
                alt="Profile"
                class="w-[110px] h-[100px] object-cover rounded-md outline outline-1 outline-black"
              />
              <p class="text-sm text-center mt-1 mb-1 font-bold">VISITOR</p>
            </div>

            <!-- Kanan -->
            <div class="w-2/3 relative p-1 pt-2 text-xs leading-relaxed">
              <p class="text-xl font-bold">
                {{ visitorToPrint.name?.length > 17
                    ? visitorToPrint.name.substring(0, 17) + '' 
                    : visitorToPrint.name }}
              </p>
              <p class="text-xs mb-3">
                {{ visitorToPrint.company ?? '-' }}
              </p>

              <p class="text-xs">Meeting room :</p>
              <p class="text-xs mb-3">
                {{ booking.room?.name || '-' }}
              </p>

              <p class="text-xs">
                Visiting: {{ booking?.meeting_with ?? '-' }}
              </p>
              <p class="text-xs">
                {{ booking?.date ?? '' }}, {{ booking?.start_time ?? '' }}
              </p>

              <QrcodeVue
                v-if="qrValue"
                :value="qrValue"
                :size="60"
                level="Q"
                class="absolute bottom-3 right-3 bg-white p-1 rounded"
                :image-settings="imageSettings"
              />
            </div>
          </div>
        </div>
      </div>
      <div v-else class="text-center text-gray-500">
        No visitor data found.
      </div>
    </div>
  </component>
</template>

<style>


@media print {
  @page {
    size: portrait;
    margin-top: 0cm;
    margin-right: 0cm;
    margin-bottom: 0cm;
    margin-left: 0cm;
  }
  body * {
    visibility: hidden;
    /* margin: 0; */
  }
  .print-area,
  .print-area * {
    visibility: visible;
  }
  .print-area {
    transform: rotate(90deg) scale(1.2) translate(5%, 20%);
    position: absolute;
    /* margin-left: 0; */
    /* margin-right: 20; */
    /* top: 10; */
    /* margin: 0; */
    /* padding: 0; */
    /* width: 100%; */
    /* height: 100%; */
  }
}
</style>
