<script setup>
import { ref, onBeforeUnmount, nextTick, watch, onMounted } from "vue";
import { Camera, RotateCcw, Printer, Home, ArrowLeft } from "lucide-vue-next";
import axios from "axios";
import { Head, router } from "@inertiajs/vue3";
import { LogIn } from "lucide-vue-next";

const visitors = ref([]);
const selectedVisitorsId = ref("");
const isNewVisitor = ref(false);

const form = ref({
  referral_code: "PUD364",
  visitors_id: null,
  name: "",
  company: "",
  position: "",
  email: "",
  phone: "",
  photo: ""
});

const videoRef = ref(null);
const canvasRef = ref(null);
const loading = ref(false);
const cameraActive = ref(false);
let stream = null;

/* ✅ Simpan data booking hasil check-in untuk print */
const checkedInBooking = ref(null);

const resetForm = () => {
  form.value = {
    referral_code: "PUD364",
    visitors_id: null,
    name: "",
    company: "",
    position: "",
    email: "",
    phone: "",
    photo: ""
  };
  visitors.value = [];
  selectedVisitorsId.value = "";
  isNewVisitor.value = false;
  checkedInBooking.value = null;
};

const fetchVisitorsData = async () => {
  if (!form.value.referral_code) return;
  loading.value = true;
  try {
    const res = await axios.get(`/visitors/by-referral`, {
      params: { referral_code: form.value.referral_code }
    });
    visitors.value = res.data.visitors || [];
    isNewVisitor.value = visitors.value.length === 0;
  } catch (err) {
    console.error(err);
    const savedReferral = form.value.referral_code;
    resetForm();
    form.value.referral_code = savedReferral;
  } finally {
    loading.value = false;
  }
};

const selectVisitors = (id) => {
  const v = visitors.value.find((x) => x.id == id);
  if (!v) return;
  selectedVisitorsId.value = id;
  form.value.visitors_id = v.id;
  form.value.name = v.name;
  form.value.company = v.company || "";
  form.value.position = v.position || "";
  form.value.email = v.email || "";
  form.value.phone = v.phone || "";

  /* ✅ Jika visitor sudah checked-in, langsung bisa print */
  if (v.status === "checked-in") {
    checkedInBooking.value = v.booking ?? { id: v.booking_id };
  } else {
    checkedInBooking.value = null;
  }
};

watch(isNewVisitor, (val) => {
  if (val) {
    selectedVisitorsId.value = "";
    form.value.visitors_id = null;
    form.value.name = "";
    form.value.company = "";
    form.value.position = "";
    form.value.email = "";
    form.value.phone = "";
  }
});

const startCamera = async () => {
  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: "user" },
      audio: false
    });
    cameraActive.value = true;
    await nextTick();
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
      try {
        await videoRef.value.play();
      } catch (_) {}
    }
  } catch (err) {
    console.error("Tidak bisa akses kamera:", err);
  }
};

const takePhoto = () => {
  const video = videoRef.value;
  const canvas = canvasRef.value;
  if (!video || !canvas) return;

  const size = 320;
  canvas.width = size;
  canvas.height = size;
  const ctx = canvas.getContext("2d");
  const aspectVideo = video.videoWidth / video.videoHeight;
  const aspectCanvas = 1;

  let sx, sy, sWidth, sHeight;
  if (aspectVideo > aspectCanvas) {
    sHeight = video.videoHeight;
    sWidth = sHeight * aspectCanvas;
    sx = (video.videoWidth - sWidth) / 2;
    sy = 0;
  } else {
    sWidth = video.videoWidth;
    sHeight = sWidth / aspectCanvas;
    sx = 0;
    sy = (video.videoHeight - sHeight) / 2;
  }
  ctx.drawImage(video, sx, sy, sWidth, sHeight, 0, 0, size, size);

  form.value.photo = canvas.toDataURL("image/png");

  if (stream) {
    stream.getTracks().forEach((t) => t.stop());
    stream = null;
  }
  cameraActive.value = false;
  if (videoRef.value) videoRef.value.srcObject = null;
};

const retake = async () => {
  form.value.photo = "";
  await startCamera();
};

onBeforeUnmount(() => {
  if (stream) {
    stream.getTracks().forEach((track) => track.stop());
    stream = null;
  }
  if (videoRef.value) videoRef.value.srcObject = null;
});

/* ✅ Print Visitor Card + Auto Print */
const printVisitorCard = (booking, visitorId) => {
  // console.log(booking);
  // console.log(visitorId);

  window.location.href = `/visitor/print?booking_id=${booking.id}&visitor_id=${visitorId}`;
};

// const printVisitorCard = (booking, visitorId) => {
//   const printWindow = window.open(
//     `/visitor/print?booking_id=${booking.id}&visitor_id=${visitorId}`,
//     "_blank",
//     "width=800,height=600"
//   );

//   if (printWindow) {
//     printWindow.onload = () => {
//       printWindow.print();
//       printWindow.onafterprint = () => printWindow.close();
//     };
//   }
// };

const checkIn = async () => {
  if (!form.value.photo) {
    alert("Photos must be taken before check-in 📷");
    return;
  }
  loading.value = true;
  try {
    const res = await axios.post("/visitors/check-in", form.value);
    alert(res.data.message);

    /* ✅ Simpan data booking hasil check-in */
    checkedInBooking.value = res.data.booking ?? null;

    /* ✅ Simpan visitor_id dari response backend */
    if (res.data.visitor) {
      form.value.visitors_id = res.data.visitor.id;
    }

    /* ✅ Auto print langsung setelah check-in */
    if (checkedInBooking.value) {
      const visitorId = form.value.visitors_id; // sudah pasti ada
      printVisitorCard(checkedInBooking.value, visitorId);
    }
  } catch (err) {
    if (err.response) {
      console.error("Error response:", err.response.data);
      alert(
        "Failed to check-in : " + (err.response.data.message || "Try again.")
      );
    } else {
      console.error(err);
      alert("Check-in failed, please try again.");
    }
  } finally {
    loading.value = false;
  }
};

/* ✅ Auto ambil referral code dari URL */
onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  form.value.referral_code = params.get("ref") || "PUD364";
  if (form.value.referral_code) {
    fetchVisitorsData();
  }
});
</script>

<template>
  <Head title="Check-In" />
  <div class="flex flex-col min-h-screen bg-gray-50">

    <!-- ✅ Header dengan tombol Home & Back -->
    <div class="flex justify-between items-center p-4">
      <button
        @click="router.visit('/')"
        class="flex items-center gap-2 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
      >
        <Home class="w-5 h-5" /> Home
      </button>

      <button
        @click="router.visit(route('check-in.index'))"
        class="flex items-center gap-2 px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300"
      >
        <ArrowLeft class="w-5 h-5" /> Back
      </button>
    </div>

    <!-- ✅ Konten utama -->
    <div class="flex items-start justify-center mt-2 flex-1 p-4">
      <div class="w-full max-w-4xl bg-white rounded-2xl shadow-lg p-8 mt-4">

        <!-- ✅ Judul di dalam form -->
        <h1 class="text-2xl font-bold text-gray-800 text-center mb-6">
          Visitor Check-In
        </h1>

        <!-- ✅ Konten check-in -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- Kamera -->
          <div class="flex flex-col items-center">
            <div
              class="w-[320px] h-[320px] rounded-lg shadow-md bg-gray-200 flex items-center justify-center cursor-pointer overflow-hidden"
              @click="!cameraActive && !form.photo && startCamera()"
            >
              <video
                v-if="cameraActive && !form.photo"
                ref="videoRef"
                autoplay
                playsinline
                muted
                width="320"
                height="320"
                class="object-cover w-full h-full"
              ></video>
              <img
                v-else-if="form.photo"
                :src="form.photo"
                alt="Captured Photo"
                class="object-cover w-full h-full"
              />
              <span v-else class="text-gray-600">
                Click to activate the camera
              </span>
            </div>

            <div class="mt-4 flex gap-3" v-if="cameraActive || form.photo">
              <button
                v-if="cameraActive && !form.photo"
                type="button"
                @click="takePhoto"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-blue-600 text-white shadow-md hover:bg-blue-700"
              >
                <Camera class="w-8 h-8" />
              </button>

              <button
                v-if="form.photo"
                type="button"
                @click="retake"
                class="flex items-center justify-center w-12 h-12 rounded-full bg-gray-200 text-gray-700 shadow-md hover:bg-gray-300"
              >
                <RotateCcw class="w-6 h-6" />
              </button>
            </div>
          </div>

          <!-- Form -->
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">
                Referral Code
              </label>
              <input
                v-model="form.referral_code"
                @blur="fetchVisitorsData"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                placeholder="Enter referral code"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">
                Visitor Name
              </label>
              <input
                v-if="isNewVisitor"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                placeholder="Masukkan nama visitor baru"
              />
              <select
                v-else
                v-model="selectedVisitorsId"
                @change="selectVisitors($event.target.value)"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              >
                <option disabled value="">-- Select Visitor --</option>
                <option v-for="v in visitors" :key="v.id" :value="v.id">
                  {{ v.name }} - {{ v.company }}
                </option>
              </select>
              <div class="flex items-center mt-2">
                <input
                  id="isNewVisitor"
                  type="checkbox"
                  v-model="isNewVisitor"
                  class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                />
                <label for="isNewVisitor" class="ml-2 text-sm text-gray-700">
                  Add New Visitors
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Company
                </label>
                <input
                  v-model="form.company"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Enter your company"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Position
                </label>
                <input
                  v-model="form.position"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Enter your Position"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Email
                </label>
                <input
                  v-model="form.email"
                  type="email"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Enter your email"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Phone Number
                </label>
                <input
                  v-model="form.phone"
                  type="tel"
                  class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Enter your phone number"
                />
              </div>
            </div>

            <div class="pt-4">
              <button
                @click="checkIn"
                :disabled="loading"
                class="w-full flex justify-center items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <template v-if="!loading">
                  <LogIn class="w-5 h-5" />
                  <span>Check-In</span>
                </template>
                <span v-else>Processing...</span>
              </button>
            </div>

            <div v-if="checkedInBooking" class="pt-4">
              <button
                @click="printVisitorCard(checkedInBooking, form.visitors_id)"
                class="w-full flex justify-center items-center bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg shadow-md transition"
              >
                <Printer class="w-5 h-5 mr-2" />
                Print Visitor Card
              </button>
            </div>
          </div>
        </div>
        <canvas ref="canvasRef" width="320" height="320" class="hidden"></canvas>
      </div>
    </div>
  </div>
</template>
