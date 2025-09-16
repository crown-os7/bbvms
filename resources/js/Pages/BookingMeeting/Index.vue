<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from "vue";
import 'quill/dist/quill.snow.css'
import QrcodeVue from 'qrcode.vue'; 
import { QrCode, Pencil, SquareX, ClipboardList, ListCheck, Download, ChevronRight, ChevronLeft } from "lucide-vue-next";
import axios from 'axios';
import html2canvas from "html2canvas";

// Props dari controller
const props = defineProps({
  bookingmeeting: {
    type: Array,
    required: true,
    default: () => []
  }
});

// state internal
const bookingData = ref(props.bookingmeeting);

// Sinkron props ke state lokal
watch(
  () => props.bookingmeeting,
  (newVal) => {
    bookingData.value = newVal;
  },
  { immediate: true }
);

// Auto refresh tiap 5 detik
let intervalId = null;
onMounted(() => {
  intervalId = setInterval(() => {
    router.reload({ only: ['bookingmeeting'] });
  }, 5000);
});
onBeforeUnmount(() => {
  clearInterval(intervalId);
});

// Modal
const showModal = ref(false);
const selectedBooking = ref(null);

const openModal = (booking) => {
  selectedBooking.value = booking;
  showModal.value = true;
};
const closeModal = () => {
  showModal.value = false;
  selectedBooking.value = null;
};

// Modal QR
const showCheckInModal = ref(false);
const showCheckOutModal = ref(false);

const openCheckInModal = (booking) => {
  selectedBooking.value = booking;
  showCheckInModal.value = true;
};
const openCheckOutModal = (booking) => {
  selectedBooking.value = booking;
  showCheckOutModal.value = true;
};
const closeCheckInModal = () => {
  showCheckInModal.value = false;
  selectedBooking.value = null;
};
const closeCheckOutModal = () => {
  showCheckOutModal.value = false;
  selectedBooking.value = null;
};

// Cancel booking
const cancelBooking = (id) => {
  if (confirm("Yakin ingin membatalkan booking ini?")) {
    router.post(route("bookingmeeting.cancel", id), {}, {
      onSuccess: () => router.reload({ only: ['bookingmeeting'] })
    });
  }
};

const goCheckIn = (booking) => `${booking.referral_code}`;
const goCheckOut = (booking) => `${booking.referral_code}`;

// 🔍 Search & Filter
const searchQuery = ref("");
const filterOption = ref("all");

// ✅ ambil user id dari Inertia page
const page = usePage();
const userId = computed(() => page.props.auth.user.id);

// ✅ Computed utama → filter + search + sort
const bookingList = computed(() => {
  const today = new Date().toISOString().split("T")[0];
  const now = new Date();

  return [...bookingData.value]
    // Filter
    .filter((booking) => {
      if (filterOption.value === "today" && booking.date !== today) return false;

      if (
        filterOption.value === "mine" &&
        !(
          (booking.created_by && booking.created_by.id === userId.value) ||
          (booking.meeting_with && booking.meeting_with.id === userId.value)
        )
      ) {
        return false;
      }

      if (["open", "in_progress", "closed", "cancelled"].includes(filterOption.value)) {
        if (booking.status !== filterOption.value) return false;
      }

      return true;
    })
    // Search
    .filter((booking) => {
      if (!searchQuery.value) return true;
      const query = searchQuery.value.toLowerCase();
      return (
        booking.visitors?.[0]?.name?.toLowerCase().includes(query) ||
        booking.visitors?.[0]?.company?.toLowerCase().includes(query) ||
        booking.purpose?.toLowerCase().includes(query) ||
        booking.meeting_with?.name?.toLowerCase().includes(query) ||
        booking.referral_code?.toLowerCase().includes(query)
      );
    })
    // Sort
    .sort((a, b) => {
      const aDateTime = new Date(`${a.date}T${a.start_time}`);
      const bDateTime = new Date(`${b.date}T${b.start_time}`);

      // Prioritaskan hari ini
      if (a.date === today && b.date !== today) return -1;
      if (b.date === today && a.date !== today) return 1;

      if (a.date === today && b.date === today) {
        const aIsPast = aDateTime < now;
        const bIsPast = bDateTime < now;
        if (!aIsPast && bIsPast) return -1;
        if (!bIsPast && aIsPast) return 1;
        if (!aIsPast && !bIsPast) return aDateTime - bDateTime;
        return bDateTime - aDateTime;
      }

      return new Date(b.date) - new Date(a.date);
    });
});

function formatTime(time) {
  if (!time) return "-";
  const [hours, minutes] = time.split(":");
  const date = new Date();
  date.setHours(hours, minutes);
  return date.toLocaleTimeString("en-US", {
    hour: "2-digit",
    minute: "2-digit",
    hour12: true,
  });
}


function formatDate(date) {
  if (!date) return "-";
  const d = new Date(date);
  const day = String(d.getDate()).padStart(2, "0");
  const month = String(d.getMonth() + 1).padStart(2, "0"); // bulan mulai dari 0
  const year = d.getFullYear();
  return `${day}-${month}-${year}`;
}


const showCheckboxes = ref(false); // ⬅️ default: checkbox tidak muncul
const selectedBookings = ref([]);

// Tombol export dipisah jadi 2 tahap
const toggleCheckboxes = () => {
  showCheckboxes.value = !showCheckboxes.value;
  if (!showCheckboxes.value) {
    selectedBookings.value = []; // reset kalau cancel
  }
};

const exportSelectedPdf = () => {
  if (!selectedBookings.value.length) {
    alert('Pilih minimal satu booking!');
    return;
  }

  axios.post(route('bookingmeeting.exportPdf'), {
      ids: selectedBookings.value
  }, {
      responseType: 'blob'
  })
  .then(res => {
      const url = window.URL.createObjectURL(new Blob([res.data], { type: 'application/pdf' }));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'MinuteOfMeeting_Selected.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();

      // reset lagi
      selectedBookings.value = [];
      showCheckboxes.value = false;
  })
  .catch(err => {
      console.error(err);
      alert('Terjadi error saat generate PDF');
  });
};

// Ambil user id dan role dari Inertia
const userRole = computed(() => page.props.auth.user.role);

const canSelectBooking = (booking) => {
  if (booking.status !== 'closed') return false; // hanya yg sudah closed

  if (userRole.value === 'admin') return true; // admin bisa semua

  // selain admin: hanya boleh kalau dia adalah meeting_with
  if (booking.meeting_with?.id === userId.value) {
    return true;
  }

  return false; // selain itu tidak boleh
};

// Pagination state
const currentPage = ref(1);
const perPage = ref(10);

// computed total halaman
const totalPages = computed(() => {
  return Math.ceil(bookingList.value.length / perPage.value);
});

// ambil data sesuai halaman
const paginatedBookings = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  const end = start + perPage.value;
  return bookingList.value.slice(start, end);
});

// fungsi pindah halaman
const goToPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
  }
};

const downloadModalAsImage = async (type = "checkin") => {
  const modalEl = document.getElementById(`modal-${type}`);
  if (!modalEl) return;

  // ✅ Sembunyikan tombol download sementara
  const downloadBtn = modalEl.querySelector(".btn-download");
  if (downloadBtn) downloadBtn.style.display = "none";

  try {
    const canvas = await html2canvas(modalEl, {
      backgroundColor: "#ffffff",
      scale: 2,
    });

    const dataUrl = canvas.toDataURL("image/png");
    const link = document.createElement("a");
    link.href = dataUrl;
    link.download = `${selectedBooking.value?.referral_code || "QR"}_${type}.png`;
    link.click();
  } catch (error) {
    console.error("Gagal download modal:", error);
  } finally {
    // ✅ Tampilkan kembali tombol download
    if (downloadBtn) downloadBtn.style.display = "block";
  }
};



</script>

<template>
  <Head title="Booking Meetings" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800 text-center">
        Guest Booking List
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-4">
          <!-- 🔍 Search -->
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search for name / Company / Purpose..."
            class="border px-3 py-2 rounded w-1/2"
          />

          <!-- 🔽 Filter -->
          <select v-model="filterOption" class="border px-3 py-2 rounded">
            <option value="all">All Meetings</option>
            <option value="today">Today's Meeting</option>
            <option value="mine">My Meeting</option>
            <option value="open">Status: Open</option>
            <option value="in_progress">Status: In Progress</option>
            <option value="closed">Status: Closed</option>
            <option value="cancelled">Status: Cancelled</option>
          </select>
        </div>
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div v-if="bookingList.length" class="overflow-x-auto">
              <table class="w-full border-collapse min-w-max">
                <thead>
                  <tr class="bg-gray-100">
                    <!-- Checkbox Print hanya untuk selain security -->
                    <th 
                      v-if="$page.props.auth.user.role !== 'security' && showCheckboxes" 
                      class="border px-4 py-2 text-center"
                    >
                      Export
                    </th>
                    <th class="border px-4 py-2 text-center">#</th>
                    <th class="border px-4 py-2 text-center">QR</th>
                    <th 
                      v-if="$page.props.auth.user.role !== 'security'" 
                      class="border px-4 py-2 text-center"
                    >
                      Action
                    </th>
                    <th class="border px-4 py-2 text-center">Referral Code</th>
                    <th class="border px-4 py-2 text-center">Visitor Name</th>
                    <th class="border px-4 py-2 text-center">Company</th>
                    <th class="border px-4 py-2 text-center">Room</th>
                    <th class="border px-4 py-2 text-center">Date</th>
                    <th class="border px-4 py-2 text-center">Start Time</th>
                    <th class="border px-4 py-2 text-center">End Time</th>
                    <th class="border px-4 py-2 text-center">Purpose</th>
                    <th class="border px-4 py-2 text-center">Meet</th>
                    <th class="border px-4 py-2 text-center">Status</th>
                    <!-- Kolom Detail hanya kalau bukan security -->
                    <th class="border px-4 py-2 text-center">Details</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                  v-for="(booking, index) in paginatedBookings"
                  :key="booking.id"
                  :class="{
                    'bg-gray-100 text-gray-500 line-through': booking.status === 'cancelled'
                  }"
                >
                    <!-- Checkbox Export  -->
                    <td 
                      v-if="$page.props.auth.user.role !== 'security' && showCheckboxes" 
                      class="border px-4 py-2 text-center"
                      
                    >
                      <template v-if="canSelectBooking(booking)">
                        <input
                          type="checkbox"
                          v-model="selectedBookings"
                          :value="booking.id"
                        />
                      </template>
                    </td>

                    <!-- Nomor urut -->
                    <td class="border p-2 text-center align-middle">
                      {{ (currentPage - 1) * perPage + index + 1 }}
                    </td>

                    <!-- QR Checkin/Checkout -->
                    <td class="border px-4 py-2 space-y-2">
                      <!-- ✅ Button QR Check-In -->
                      <button
                        v-if="booking.status === 'open' && (
                                $page.props.auth.user.role === 'admin' || 
                                $page.props.auth.user.role === 'security' ||
                                (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                                (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                              )"
                        @click="openCheckInModal(booking)"
                        class="flex items-center gap-2 bg-blue-500 text-white px-3 py-1 rounded"
                      >
                        <QrCode class="w-5 h-5" />
                        IN
                      </button>

                      <!-- ✅ Button QR Check-Out -->
                      <button
                        v-if="booking.status === 'in_progress' && (
                                $page.props.auth.user.role === 'admin' || 
                                $page.props.auth.user.role === 'security' ||
                                (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                                (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                              )"
                        @click="openCheckOutModal(booking)"
                        class="flex items-center gap-2 bg-purple-500 text-white px-3 py-1 rounded"
                      >
                        <QrCode class="w-5 h-5" />
                        OUT
                      </button>
                    </td>

                    <!-- Action (hanya non-security) -->
                    <td 
                      class="border px-4 py-2 space-y-2"
                      v-if="$page.props.auth.user.role !== 'security'"
                    >
                      <!-- Edit -->
                      <Link
                        v-if="booking.status === 'open' && (
                              $page.props.auth.user.role === 'admin' || 
                              (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                              (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                            )"
                        :href="route('bookingmeeting.edit', booking.id)"
                        class="flex items-center justify-center gap-2 bg-yellow-500 text-white px-3 py-1 rounded block"
                      >
                      <Pencil class="w-5 h-5" />
                        Edit
                      </Link>

                      <!-- Cancel -->
                      <button
                        v-if="booking.status === 'open' && (
                              $page.props.auth.user.role === 'admin' || 
                              (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                              (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                            )"
                        @click="cancelBooking(booking.id)"
                        class="flex items-center justify-center gap-2 bg-red-500 text-white px-3 py-1 rounded block"
                      >
                      <SquareX class="w-5 h-5" />
                        Cancel
                      </button>

                      <!-- MOM -->
                      <Link
                        v-if="['in_progress', 'closed'].includes(booking.status) && (
                          $page.props.auth.user.role === 'admin' ||
                          (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                          (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                        )"
                        :href="route('minuteofmeeting.show', booking.id)"
                        class="flex items-center justify-center gap-2 bg-indigo-600 text-white px-3 py-1 rounded block"
                      >
                        <ClipboardList class="w-5 h-5" />
                        MOM
                      </Link>


                      <span
                        v-else-if="booking.status === 'cancelled'"
                        class="bg-gray-400 text-white px-3 py-1 rounded cursor-not-allowed block"
                      >
                        Cancelled
                      </span>

                      <span v-else></span>
                    </td>

                    <!-- Data Pengunjung -->
                    <td class="border px-4 py-2 font-semibold text-blue-600">{{ booking.referral_code || '-' }}</td>
                    <td class="border px-4 py-2">{{ booking.visitors?.[0]?.name || '-' }}</td>
                    <td class="border px-4 py-2">{{ booking.visitors?.[0]?.company || '-' }}</td>
                    <td class="border px-4 py-2">{{ booking.room?.name || '-' }}</td>
                    <td class="border px-4 py-2">{{ formatDate(booking.date) }}</td>
                    <td class="border px-4 py-2">{{ formatTime(booking.start_time) }}</td>
                    <td class="border px-4 py-2">{{ formatTime(booking.end_time) }}</td>
                    <td class="border px-4 py-2">{{ booking.purpose || '-' }}</td>
                    <td class="border px-4 py-2">{{ booking.meeting_with?.name || '-' }}</td>

                    <!-- Status -->
                    <td class="border px-4 py-2">
                      <span
                        :class="{
                          'text-green-600 font-semibold': booking.status === 'in_progress',
                          'text-gray-600': booking.status === 'open',
                          'text-red-600': booking.status === 'cancelled',
                          'text-blue-600': booking.status === 'closed'
                        }"
                      >
                        {{ booking.status || '-' }}
                      </span>
                    </td>

                    <!-- Detail hanya untuk selain security -->
                    <td class="border px-4 py-2">
                      <button
                        v-if="(
                          $page.props.auth.user.role === 'admin' || 
                          $page.props.auth.user.role === 'security' || 
                          (booking.created_by && booking.created_by.id === $page.props.auth.user.id) ||
                          (booking.meeting_with && booking.meeting_with.id === $page.props.auth.user.id)
                        )"
                        @click="openModal(booking)"
                        class="bg-indigo-500 text-white px-3 py-1 rounded"
                      >
                        Details
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="text-center text-gray-500">
              Tidak ada data booking meeting
            </div>
          </div>
        </div>

        <!-- Wrapper bawah tabel -->
        <div class="flex justify-between items-center mt-4">
          <!-- Tombol-tombol hanya tampil kalau bukan security -->
          <div v-if="$page.props.auth.user.role !== 'security'" class="flex gap-2">
            <!-- Tombol toggle -->
            <button
              @click="toggleCheckboxes"
              class="flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded"
            >
              <ListCheck class="w-5 h-5" />
              {{ showCheckboxes ? 'Cancel Selection' : 'Select to Export' }}
            </button>

            <!-- Tombol export -->
            <button
              v-if="showCheckboxes && selectedBookings.length > 0"
              @click="exportSelectedPdf"
              class="flex items-center justify-center gap-2 bg-green-600 text-white px-4 py-2 rounded"
            >
              <Download class="w-5 h-5" />
              Export Selected PDF
            </button>
          </div>

          <!-- Pagination -->
          <div class="flex items-center gap-2">
            <span class="mr-4">Page {{ currentPage }} From {{ totalPages }}</span>

            <button 
              class="flex items-center justify-center gap-2 px-3 py-1 bg-gray-200 rounded disabled:opacity-50"
              :disabled="currentPage === 1"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="w-5 h-5" />
              Prev
            </button>

            <button 
              class="flex items-center justify-center gap-2 px-3 py-1 bg-gray-200 rounded disabled:opacity-50"
              :disabled="currentPage === totalPages"
              @click="goToPage(currentPage + 1)"
            >
              Next
              <ChevronRight class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Detail -->
    <div 
      v-if="showModal" 
      class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-lg shadow-lg max-w-lg w-full p-6 relative max-h-[80vh] overflow-y-auto">
        <button class="absolute top-2 right-2 text-gray-600 hover:text-black" @click="closeModal">✖</button>
        <h3 class="text-lg font-bold mb-4">Booking Details</h3>

        <div v-if="selectedBooking">
          
          <!-- ✅ Security akses terbatas -->
          <template v-if="$page.props.auth.user.role === 'security'">
            <!-- Visitor Info -->
            <div
              v-for="(visitors, index) in selectedBooking.visitors"
              :key="visitors.id"
              class="mb-4 border-b pb-4"
            >
              <h4 class="text-md font-semibold mb-2">Tamu {{ index + 1 }}</h4>
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <img 
                    v-if="visitors.photo"
                    :src="visitors.photo"
                    alt="Foto Pengunjung" 
                    class="w-24 h-24 rounded-full object-cover border"
                  />
                  <div v-else class="w-24 h-24 rounded-full border flex items-center justify-center text-gray-400">
                    -
                  </div>
                </div>
                <div class="flex-1 text-sm text-gray-700">
                  <p><strong>Name:</strong> {{ visitors.name || '-' }}</p>
                  <p><strong>Company:</strong> {{ visitors.company || '-' }}</p>
                  <p><strong>Phone:</strong> {{ visitors.phone || '-' }}</p>
                  <p><strong>Status:</strong> {{ visitors.status || '-' }}</p>
                </div>
              </div>
            </div>

            <!-- Booking Info -->
            <p><strong>Referral Code:</strong> {{ selectedBooking.referral_code || '-' }}</p>
            <p><strong>Room:</strong> {{ selectedBooking.room?.name || '-' }}</p>
            <p><strong>Date:</strong> {{ formatDate(selectedBooking.date) || '-' }}</p>
            <p><strong>Start Time:</strong> {{ formatTime (selectedBooking.start_time) || '-' }}</p>
            <p><strong>End Time:</strong> {{ formatTime (selectedBooking.end_time) || '-' }}</p>
            <p><strong>Duration:</strong> {{ selectedBooking.duration || '-' }} menit</p>
            <p><strong>Purpose:</strong> {{ selectedBooking.purpose || '-' }}</p>
            <p><strong>Meeting With:</strong> {{ selectedBooking.meeting_with?.name || '-' }}</p>
            <p><strong>Booking Status:</strong> {{ selectedBooking.status || '-' }}</p>
          </template>

          <!-- ✅ Role selain security -->
          <template v-else>
            <!-- Visitor Info -->
            <div
              v-for="(visitors, index) in selectedBooking.visitors"
              :key="visitors.id"
              class="mb-4 border-b pb-4"
            >
              <h4 class="text-md font-semibold mb-2">Tamu {{ index + 1 }}</h4>
              <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                  <img 
                    v-if="visitors.photo"
                    :src="visitors.photo"
                    alt="Foto Pengunjung" 
                    class="w-24 h-24 rounded-full object-cover border"
                  />
                  <div v-else class="w-24 h-24 rounded-full border flex items-center justify-center text-gray-400">
                    -
                  </div>
                </div>
                <div class="flex-1 text-sm text-gray-700">
                  <p><strong>Name:</strong> {{ visitors.name || '-' }}</p>
                  <p><strong>Company:</strong> {{ visitors.company || '-' }}</p>
                  <p><strong>Phone:</strong> {{ visitors.phone || '-' }}</p>
                  <p><strong>Status:</strong> {{ visitors.status || '-' }}</p>

                  <!-- Jabatan & Email hanya Admin, Creator, Meeting_with -->
                  <p v-if="$page.props.auth.user.role === 'admin' 
                          || $page.props.auth.user.id === selectedBooking.created_by?.id 
                          || $page.props.auth.user.id === selectedBooking.meeting_with?.id">
                    <strong>Position:</strong> {{ visitors.position || '-' }}
                  </p>
                  <p v-if="$page.props.auth.user.role === 'admin' 
                          || $page.props.auth.user.id === selectedBooking.created_by?.id 
                          || $page.props.auth.user.id === selectedBooking.meeting_with?.id">
                    <strong>Email:</strong> {{ visitors.email || '-' }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Booking Info -->
            <p><strong>Referral Code:</strong> {{ selectedBooking.referral_code || '-' }}</p>
            <p><strong>Room:</strong> {{ selectedBooking.room?.name || '-' }}</p>
            <p><strong>Date:</strong> {{ formatDate(selectedBooking.date) || '-' }}</p>
            <p><strong>Start Time:</strong> {{ formatTime (selectedBooking.start_time) || '-' }}</p>
            <p><strong>End Time:</strong> {{ formatTime (selectedBooking.end_time) || '-' }}</p>
            <p><strong>Duration:</strong> {{ selectedBooking.duration || '-' }} menit</p>
            <p><strong>Purpose:</strong> {{ selectedBooking.purpose || '-' }}</p>
            <p><strong>Meeting With:</strong> {{ selectedBooking.meeting_with?.name || '-' }}</p>
            <p><strong>Booking Status:</strong> {{ selectedBooking.status || '-' }}</p>

            <!-- Minute of Meeting -->
            <div v-if="($page.props.auth.user.role === 'admin' 
                        || $page.props.auth.user.id === selectedBooking.created_by?.id 
                        || $page.props.auth.user.id === selectedBooking.meeting_with?.id) 
                        && selectedBooking.minute_of_meeting">
              <h4 class="text-md font-semibold mb-2">Minute of Meeting</h4>
              <div
                class="ql-editor border p-3 rounded bg-gray-50"
                v-html="selectedBooking.minute_of_meeting.details"
              ></div>
            </div>
          </template>
        </div>
      </div>
    </div>

<!-- Modal QR Check-In -->
  <div
    v-if="showCheckInModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    @click.self="closeCheckInModal"
  >
    <div
      id="modal-checkin"
      class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6 relative text-center"
    >
      <button
        class="absolute top-2 right-2 text-gray-600 hover:text-black"
        @click="closeCheckInModal"
      >
        ✖
      </button>
      <h3 class="text-lg font-bold mb-4">QR Check-In</h3>

      <!-- ✅ QR Code dengan Logo -->
      <div class="relative inline-block">
        <qrcode-vue 
          v-if="selectedBooking" 
          :value="goCheckIn(selectedBooking)" 
          :size="200" 
          level="H" 
          class="mx-auto"
        />
        <!-- Logo di tengah -->
        <img 
          src="/img/logo/logo-bb.png" 
          class="absolute top-1/2 left-1/2 w-12 h-12 -translate-x-1/2 -translate-y-1/2 bg-white p-1"
          alt="Logo"
        />
      </div>

      <p class="mt-2 text-sm text-gray-600">Scan for Check-In</p>
      <p>{{ selectedBooking?.referral_code || "-" }}</p>

      <button
        @click="downloadModalAsImage('checkin')"
        class="btn-download mt-3 bg-green-600 text-white px-4 py-2 rounded w-full"
      >
        Download QR Code
      </button>
    </div>
  </div>

  <!-- Modal QR Check-Out -->
  <div
    v-if="showCheckOutModal"
    class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
    @click.self="closeCheckOutModal"
  >
    <div
      id="modal-checkout"
      class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6 relative text-center"
    >
      <button
        class="absolute top-2 right-2 text-gray-600 hover:text-black"
        @click="closeCheckOutModal"
      >
        ✖
      </button>
      <h3 class="text-lg font-bold mb-4">QR Check-Out</h3>


    <!-- ✅ QR Code + Logo di Tengah -->
    <div class="relative inline-block">
      <QrcodeVue
        v-if="selectedBooking"
        :value="goCheckOut(selectedBooking)"
        :size="200"
        level="H"
        class="mx-auto"
      />
      <!-- Logo di Tengah QR -->
      <img
        src="/img/logo/logo-bb.png" 
        class="absolute top-1/2 left-1/2 w-12 h-12 -translate-x-1/2 -translate-y-1/2 bg-white p-1 "
        alt="Logo"
      />
    </div>

      <p class="mt-2 text-sm text-gray-600">Scan for Check-Out</p>
      <p>{{ selectedBooking?.referral_code || "-" }}</p>

      <button
        @click="downloadModalAsImage('checkout')"
        class="btn-download mt-3 bg-green-600 text-white px-4 py-2 rounded w-full"
      >
        Download QR Code
      </button>
    </div>
  </div>

  </AuthenticatedLayout>
</template>
