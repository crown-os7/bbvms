<script setup>
import { ref, watch, onMounted, computed } from "vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { Building, SquarePlus, Repeat2, Trash, Check, ChevronDown } from "lucide-vue-next";
import {
  Combobox,
  ComboboxInput,
  ComboboxOptions,
  ComboboxOption,
  ComboboxButton,
  TransitionRoot,
} from "@headlessui/vue";
import axios from "axios";

// Props dari controller
const props = defineProps({
  booking: { type: Object, required: true },
  rooms: { type: Array, required: true },
  employees: { type: Array, required: true },
});

// Ambil role dan id user dari Inertia
const page = usePage();
const userRole = page.props.auth.user.role;
const userId = page.props.auth.user.id;
const userName = page.props.auth.user.name;

// Toggle pengunjung dari perusahaan berbeda
const differentCompany = ref(false);

// Form booking
const form = useForm({
  room_id: props.booking.room_id ?? "",
  visitors: props.booking.visitors?.length
    ? props.booking.visitors.map(v => ({
        id: v.id,
        name: v.name ?? "",
        company: v.company ?? "",
        position: v.position ?? "",
        email: v.email ?? "",
        phone: v.phone ?? "",
      }))
    : [
        { name: props.booking.name ?? "", company: props.booking.company ?? "", position: props.booking.position ?? "" }
      ],
  date: props.booking.date ?? "",
  start_time: props.booking.start_time ?? "",
  duration: props.booking.duration ?? "",
  purpose: props.booking.purpose ?? "",
  meeting_with: props.booking.meeting_with ?? (userRole === "employee" ? userId : ""),
});

// Inisialisasi differentCompany
onMounted(() => {
  if (form.visitors.length > 1) {
    differentCompany.value = !form.visitors.every(
      v => v.company === form.visitors[0]?.company
    );
  }
});

// Modal pilih ruangan
const openModal = ref(false);
const selectedRoomId = ref(props.booking.room_id);
const currentBookings = ref([...props.rooms]);

// Tambah & hapus visitor
function addVisitors() {
  form.visitors.push({
    name: "",
    company: differentCompany.value ? "" : form.visitors[0]?.company || "",
    position: "",
    email: "",
    phone: "",
  });
}

function removeVisitors(index) {
  form.visitors.splice(index, 1);
}

// Watch toggle perusahaan berbeda
watch(differentCompany, (val) => {
  if (!val) {
    const mainCompany = form.visitors[0]?.company ?? "";
    form.visitors.forEach((v, idx) => {
      if (idx > 0) v.company = mainCompany;
    });
  }
});

// Watch company utama jika semua sama
watch(() => form.visitors[0]?.company, (val) => {
  if (!differentCompany.value) {
    form.visitors.forEach((v, idx) => {
      if (idx > 0) v.company = val;
    });
  }
});

// Watch tanggal/jam/durasi -> cek status room
watch(
  () => [form.date, form.start_time, form.duration],
  async ([date, start, duration]) => {
    if (!date || !start || !duration) return;
    try {
      const { data } = await axios.get(route("check.room.status"), {
        params: {
          date,
          start_time: start,
          duration,
          booking_id: props.booking.id,
        },
      });
      currentBookings.value = data.availableRooms ?? [];
      if (selectedRoomId.value && !currentBookings.value.some(r => r.id === selectedRoomId.value)) {
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

const isAvailable = (roomId) => currentBookings.value.some((r) => r.id === roomId);
const isDisabledRoom = (roomId) => !isAvailable(roomId);

const chooseRoom = () => {
  form.room_id = selectedRoomId.value;
  openModal.value = false;
};

// Combobox Meeting With
const query = ref("");
const filteredEmployees = computed(() =>
  query.value === ""
    ? props.employees
    : props.employees.filter((e) =>
        e.name.toLowerCase().includes(query.value.toLowerCase())
      )
);

// Submit form update
const updateBooking = () => {
  form.put(route("bookingmeeting.update", props.booking.id), {
    preserveScroll: true,
    onSuccess: () => console.log("✅ Update sukses"),
    onError: (errors) => console.error("❌ Error:", errors),
  });
};
</script>

<template>
  <Head title="Edit Booking Meeting" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-center text-gray-800">Edit Meeting Booking</h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
          <div class="max-w-xl mx-auto bg-white shadow-md rounded-lg p-6 space-y-4">

            <form @submit.prevent="updateBooking" class="space-y-4">
              <!-- Pilih Ruangan -->
              <div>
                <div class="grid grid-cols-2 gap-4 p-3 border rounded bg-gray-50">
                  <div>
                    <p class="text-sm font-medium">Room</p>
                    <p class="border rounded p-2 bg-white">{{ rooms.find((r) => r.id === form.room_id)?.name || "-" }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Date</p>
                    <p class="border rounded p-2 bg-white">{{ form.date || "-" }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Time</p>
                    <p class="border rounded p-2 bg-white">{{ form.start_time || "-" }}</p>
                  </div>
                  <div>
                    <p class="text-sm font-medium">Duration</p>
                    <p class="border rounded p-2 bg-white">{{ form.duration ? form.duration + " Minute" : "-" }}</p>
                  </div>
                </div>
                <button type="button" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded mb-3" @click="openModal = true">
                  <Building class="w-5 h-5" />
                  Select Room
                </button>
              </div>

              <!-- Visitors -->
              <div v-for="(visitors, index) in form.visitors" :key="index" class="border p-4 rounded mb-4">
                <h3 class="font-semibold mb-2">Visitor {{ index + 1 }}</h3>
                <div class="mb-2">
                  <label class="block text-sm font-medium mb-1">Name</label>
                  <input type="text" v-model="visitors.name" class="border rounded w-full p-2"/>
                </div>
                <div>
                  <label class="block text-sm font-medium mb-1">Company</label>
                  <input type="text" v-model="visitors.company" 
                    class="border rounded w-full p-2"
                    :readonly="!differentCompany && form.visitors.length > 1"
                  />
                </div>
                <div class="flex justify-end">
                  <button type="button" class="flex items-center justify-center gap-2 mt-4 bg-red-500 text-white px-3 py-1 rounded" @click="removeVisitors(index)" v-if="form.visitors.length > 1">
                    <Trash class="w-5 h-5" />
                    Delete
                  </button>
                </div>
              </div>

              <!-- Toggle perusahaan berbeda -->
              <div class="flex items-center space-x-2">
                <label class="font-medium">Visitors from different companies?</label>
                <input type="checkbox" v-model="differentCompany" class="form-checkbox"/>
              </div>

              <button type="button" class="w-full flex items-center justify-center gap-2 bg-green-500 text-white px-4 py-2 rounded mb-4" @click="addVisitors">
                <SquarePlus class="w-5 h-5" />
                Add Visitor
              </button>

              <!-- Purpose -->
              <div>
                <label class="block text-sm font-medium mb-1">Purpose</label>
                <textarea v-model="form.purpose" class="border rounded w-full p-2"></textarea>
              </div>

              <!-- Meeting With -->
              <div>
                <label class="block text-sm font-medium mb-1">Meeting With</label>
                <input
                  v-if="userRole === 'employee'"
                  type="text"
                  :value="userName"
                  class="border rounded w-full p-2 bg-gray-100"
                  readonly
                />

                <Combobox v-else v-model="form.meeting_with">
                  <div class="relative">
                    <div class="relative w-full cursor-default overflow-hidden rounded-lg border bg-white text-left">
                      <ComboboxInput
                        class="w-full border-none py-2 pl-3 pr-10 leading-5 text-gray-900 focus:ring-0"
                        placeholder="Search employee..."
                        @change="query = $event.target.value"
                        :displayValue="id => props.employees.find(e => e.id === id)?.name || ''"
                      />
                      <ComboboxButton class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <ChevronDown class="h-5 w-5 text-black" />
                      </ComboboxButton>
                    </div>
                    <TransitionRoot
                      leave="transition ease-in duration-100"
                      leave-from="opacity-100"
                      leave-to="opacity-0"
                      @after-leave="query = ''"
                    >
                      <ComboboxOptions class="absolute mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm z-10">
                        <ComboboxOption
                          v-for="emp in filteredEmployees"
                          :key="emp.id"
                          :value="emp.id"
                          as="template"
                          v-slot="{ active, selected }"
                        >
                          <li
                            :class="[
                              'relative cursor-default select-none py-2 pl-10 pr-4',
                              active ? 'bg-blue-600 text-white' : 'text-gray-900',
                            ]"
                          >
                            <span
                              :class="[
                                'block truncate',
                                selected ? 'font-medium' : 'font-normal',
                              ]"
                            >
                              {{ emp.name }}
                            </span>
                            <span
                              v-if="selected"
                              class="absolute inset-y-0 left-0 flex items-center pl-3 text-blue-600"
                            >
                              <Check class="h-5 w-5" />
                            </span>
                          </li>
                        </ComboboxOption>
                      </ComboboxOptions>
                    </TransitionRoot>
                  </div>
                </Combobox>
              </div>

              <button type="submit" class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                <Repeat2 class="w-5 h-5" />
                Update Meeting Booking
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Pilih Ruangan -->
    <div v-if="openModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50" @click.self="openModal = false">
      <div class="bg-white rounded-lg shadow-lg w-11/12 max-w-4xl p-6 relative">
        <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" @click="openModal = false">✕</button>
        <h3 class="text-lg font-bold mb-4">Select Room</h3>
        <div class="grid grid-cols-2 gap-4">
          <label v-for="room in rooms" :key="room.id" class="flex items-center space-x-4 border rounded-lg p-4 hover:bg-gray-100" :class="isDisabledRoom(room.id) ? 'opacity-50 cursor-not-allowed' : ''">
            <input type="radio" name="room" :value="room.id" v-model="selectedRoomId" :disabled="isDisabledRoom(room.id)" />
            <img :src="`/img/room/${room.imgroom}`" :alt="room.name" class="w-24 h-16 object-cover rounded-md" />
            <div>
              <p class="font-bold">{{ room.name }}</p>
              <p class="text-sm text-gray-500">Capacity: {{ room.capacity }} Person</p>
              <p class="text-sm text-gray-500">Facility: {{ room.facility }}</p>
              <p class="text-sm" :class="!isDisabledRoom(room.id) ? 'text-green-600' : 'text-red-600'">{{ !isDisabledRoom(room.id) ? 'Available' : 'Not available' }}</p>
            </div>
          </label>
        </div>
        <div class="mt-6 space-y-4">
          <div>
            <label class="block text-sm font-medium mb-1">Date</label>
            <input type="date" v-model="form.date" class="border rounded w-full p-2"/>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Start Time</label>
            <input type="time" v-model="form.start_time" class="border rounded w-full p-2"/>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Duration (Minute)</label>
            <input type="number" v-model="form.duration" min="30" step="30" class="border rounded w-full p-2"/>
          </div>
        </div>
        <div class="flex justify-end gap-2 mt-6">
          <button class="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50" :disabled="!selectedRoomId" @click="chooseRoom">Select & Save</button>
          <button class="bg-gray-300 px-4 py-2 rounded" @click="openModal = false">Close</button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
