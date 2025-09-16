<script setup>
import { ref, watch } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { Building, SquarePlus, BookText, Trash } from "lucide-vue-next";
import axios from "axios";

// Props dari controller
const props = defineProps({
  rooms: { type: Array, required: true },
  bookings: { type: Array, default: () => [] },
  employees: { type: Array, default: () => [] },
  auth: { type: Object, required: true },
});

const page = usePage();
const user = props.auth.user;

// Toggle pengunjung dari perusahaan berbeda
const differentCompany = ref(false);

// === FORM ===
const form = useForm({
  room_id: null,
  company: "",
  visitors: [
    {
      name: "",
      company: "",
    },
  ],
  date: "",
  start_time: "",
  duration: "",
  purpose: "",
  meeting_with: user.role === "employee" ? user.id : "",
});

function addVisitors() {
  form.visitors.push({
    name: "",
    company: differentCompany.value ? "" : form.company,
  });
}

function removeVisitors(index) {
  form.visitors.splice(index, 1);
}

// Watch toggle differentCompany
watch(differentCompany, (val) => {
  if (!val) {
    form.visitors.forEach((v) => (v.company = form.company));
  } else {
    form.visitors.forEach((v) => (v.company = ""));
  }
});

// Watch company utama
watch(() => form.company, (val) => {
  if (!differentCompany.value) {
    form.visitors.forEach((v) => (v.company = val));
  }
});

const openModal = ref(false);
const selectedRoomId = ref(null);
const currentBookings = ref([...props.bookings]);

// cek status ruangan setiap kali tanggal, jam, durasi berubah
watch(
  () => [form.date, form.start_time, form.duration],
  async ([date, start, duration]) => {
    if (!date || !start || !duration) return;

    try {
      const { data } = await axios.get(route("check.room.status"), {
        params: { date, start_time: start, duration },
      });

      // ✅ filter cancelled agar tidak dianggap bentrok
      const availableRooms = Array.isArray(data.availableRooms)
        ? data.availableRooms.filter((r) => r.status !== "cancelled")
        : [];

      currentBookings.value = availableRooms;

      if (
        selectedRoomId.value &&
        !availableRooms.some((r) => r.id === selectedRoomId.value)
      ) {
        selectedRoomId.value = null;
        form.room_id = null;
      }
    } catch (e) {
      console.error("Gagal cek status ruangan:", e);
      currentBookings.value = [];
      selectedRoomId.value = null;
      form.room_id = null;
    }
  }
);

const isAvailable = (roomId) =>
  currentBookings.value.some((r) => r.id === roomId);
const isDisabledRoom = (roomId) => !isAvailable(roomId);

const chooseRoom = () => {
  form.room_id = selectedRoomId.value;
  openModal.value = false;
};

// flash message
const flash = page.props.flash ?? {};
const showModal = ref(false);
const referralCode = ref(null);

function copyReferralCode() {
  if (referralCode.value) {
    navigator.clipboard
      .writeText(referralCode.value)
      .then(() => alert("Referral code berhasil dicopy!"))
      .catch(() => alert("Gagal menyalin referral code"));
  }
}

function submitForm() {
  form.post(route("bookingmeeting.store"), {
    onSuccess: () => {
      referralCode.value = page.props.flash?.referral_code || null;

      if (referralCode.value) {
        showModal.value = true;
      }

      form.reset();
      if (user.role === "employee") {
        form.meeting_with = user.id;
      }
      selectedRoomId.value = null;
    },
  });
}
</script>

<template>
  <Head title="Form Booking Meeting" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-center text-gray-800">
        Meeting Booking Form
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
          <div class="max-w-xl mx-auto bg-white shadow-md rounded-lg p-6 space-y-6">
            <!-- Flash -->
            <div
              v-if="flash.success"
              class="bg-green-100 text-green-800 p-3 rounded"
            >
              {{ flash.success }}
            </div>
            <div
              v-if="flash.referral_code"
              class="bg-blue-100 text-blue-800 p-3 rounded"
            >
              Referral Code Anda:
              <strong>{{ flash.referral_code }}</strong>
            </div>
            <div
              v-if="flash.conflict_error"
              class="bg-red-100 text-red-800 p-3 rounded"
            >
              ⚠️ {{ flash.conflict_error }}
            </div>

            <form @submit.prevent="submitForm" class="space-y-4">

              <div>
                <div
                  class="grid grid-cols-2 gap-4 p-3 border rounded bg-gray-50"
                >
                  <div>
                    <p class="text-sm font-medium">Room</p>
                    <p class="border rounded p-2 bg-white">
                      {{
                        rooms.find((r) => r.id === form.room_id)?.name || "-"
                      }}
                    </p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Date</p>
                    <p class="border rounded p-2 bg-white">
                      {{ form.date || "-" }}
                    </p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Time</p>
                    <p class="border rounded p-2 bg-white">
                      {{ form.start_time || "-" }}
                    </p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Duration</p>
                    <p class="border rounded p-2 bg-white">
                      {{ form.duration ? form.duration + " Minute" : "-" }}
                    </p>
                  </div>
                </div>
              </div>
              <!-- Pilih Ruangan -->
                <!-- <label class="block text-sm font-medium mb-1"
                  >Pilih Ruangan</label
                > -->
                <button
                  type="button"
                  class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded mb-3"
                  @click="openModal = true"
                >
                  <Building class="w-5 h-5" />
                  Select Room
                </button>

              <!-- Visitors -->
              <div
                v-for="(visitors, index) in form.visitors"
                :key="index"
                class="border p-4 rounded mb-4"
              >
                <h3 class="font-semibold mb-2">
                  Visitor {{ index + 1 }}
                </h3>
                <div class="mb-2">
                  <label class="block text-sm font-medium mb-1">Name</label>
                  <input
                    type="text"
                    v-model="visitors.name"
                    class="border rounded w-full p-2"
                  />
                </div>
                <div class="mb-2" v-if="differentCompany">
                  <label class="block text-sm font-medium mb-1"
                    >Company</label
                  >
                  <input
                    type="text"
                    v-model="visitors.company"
                    class="border rounded w-full p-2"
                    placeholder=""
                  />
                </div>
                <div class="flex justify-end">
                  <button
                    type="button"
                    class="flex items-center justify-center gap-2 bg-red-500 text-white px-3 py-1 rounded"
                    @click="removeVisitors(index)"
                    v-if="form.visitors.length > 1"
                  >
                  <Trash class="w-5 h-5" />
                    Delete
                  </button>
                </div>
              </div>
              <button
                type="button"
                class="w-full flex items-center justify-center gap-2 bg-green-500 text-white px-4 py-2 rounded mb-4"
                @click="addVisitors"
              >
                <SquarePlus class="w-5 h-5" />
                Add Visitors
              </button>


              <!-- Toggle perusahaan berbeda -->
              <div class="flex items-center space-x-2">
                <label class="font-medium"
                  >Visitors from different companies?</label
                >
                <input
                  type="checkbox"
                  v-model="differentCompany"
                  class="form-checkbox"
                />
              </div>

              <!-- Company utama -->
              <div v-if="!differentCompany">
                <label class="block text-sm font-medium mb-1">Company</label>
                <input
                  v-model="form.company"
                  type="text"
                  class="border rounded w-full p-2"
                />
              </div>

              <!-- Purpose -->
              <div>
                <label class="block text-sm font-medium mb-1">Purpose</label>
                <textarea
                  v-model="form.purpose"
                  class="border rounded w-full p-2"
                ></textarea>
              </div>

              <!-- Meeting With -->
              <div>
                <label class="block text-sm font-medium mb-1"
                  >Meeting With</label
                >
                <input
                  v-if="user.role === 'employee'"
                  type="text"
                  :value="user.name"
                  class="border rounded w-full p-2 bg-gray-100"
                  readonly
                />
                <select
                  v-else-if="user.role === 'admin' || user.role === 'security'"
                  v-model="form.meeting_with"
                  class="border rounded w-full p-2"
                >
                  <option disabled value="">-- Select Employee --</option>
                  <option
                    v-for="emp in props.employees"
                    :key="emp.id"
                    :value="emp.id"
                  >
                    {{ emp.name }}
                  </option>
                </select>
              </div>

              <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >
              <BookText class="w-5 h-5" />
                Booking Meeting
              </button>
            </form>
          </div>
        </div>
      </div>

    <!-- Modal Pilih Ruangan -->
    <div
      v-if="openModal"
      class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50"
      @click.self="openModal = false"
    >
      <div
        class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl p-6 relative"
      >
        <button
          class="absolute top-3 right-3 text-gray-500 hover:text-gray-700"
          @click="openModal = false"
        >
          ✕
        </button>
        <h3 class="text-lg font-bold mb-4">Select Room</h3>
        <div class="grid grid-cols-2 gap-4">
          <label
            v-for="room in rooms"
            :key="room.id"
            class="flex items-center space-x-4 border rounded-lg p-4 hover:bg-gray-100"
            :class="
              isDisabledRoom(room.id)
                ? 'opacity-50 cursor-not-allowed'
                : ''
            "
          >
            <input
              type="radio"
              name="room"
              :value="room.id"
              v-model="selectedRoomId"
              :disabled="isDisabledRoom(room.id)"
            />
            <img
              :src="`/img/room/${room.imgroom}`"
              :alt="room.name"
              class="w-24 h-16 object-cover rounded-md"
            />
            <div>
              <p class="font-bold">{{ room.name }}</p>
              <p class="text-sm text-gray-500">
                Capacity: {{ room.capacity }} Person
              </p>
              <p class="text-sm text-gray-500">
                Facility: {{ room.facility }}
              </p>
              <p
                class="text-sm"
                :class="
                  !isDisabledRoom(room.id) ? 'text-green-600' : 'text-red-600'
                "
              >
                {{
                  !isDisabledRoom(room.id) ? "Available" : "Not available"
                }}
              </p>
            </div>
          </label>
        </div>
        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Date</label>
            <input
              type="date"
              v-model="form.date"
              class="border rounded w-full p-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Start Time</label>
            <input
              type="time"
              v-model="form.start_time"
              class="border rounded w-full p-2"
            />
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Duration (Minute)</label>
            <input
              type="number"
              v-model="form.duration"
              min="30"
              step="30"
              class="border rounded w-full p-2"
            />
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
          <button
            class="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50"
            :disabled="!selectedRoomId"
            @click="chooseRoom"
          >
            Pilih & Simpan
          </button>
          <button
            class="bg-gray-300 px-4 py-2 rounded"
            @click="openModal = false"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>

    <!-- Popup Referral Code -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center"
    >
      <div
        class="bg-white rounded-lg shadow-lg p-6 w-96 text-center"
      >
        <h2 class="text-xl font-bold mb-4">Booking Berhasil</h2>
        <p>Referral Code Anda:</p>
        <div class="flex items-center justify-center gap-2 mt-2">
          <p class="text-2xl font-mono text-blue-600">{{ referralCode }}</p>
          <button
            @click="copyReferralCode"
            class="bg-gray-200 text-gray-700 px-2 py-1 rounded text-sm hover:bg-gray-300"
          >
            Copy
          </button>
        </div>
        <button
          @click="showModal = false"
          class="mt-4 bg-blue-600 text-white px-4 py-2 rounded"
        >
          Tutup
        </button>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
