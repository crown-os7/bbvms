<script setup>
import { ref } from "vue"
import { Head, useForm } from "@inertiajs/vue3"
import { QuillEditor } from "@vueup/vue-quill"
import "@vueup/vue-quill/dist/vue-quill.snow.css"
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue"
import { Save, Sheet, FileText } from 'lucide-vue-next';

const props = defineProps({
  meeting: { type: Object, required: true }, // data meeting
  minuteofmeeting: { type: Object, default: null }, // data MOM dari DB (kalau sudah ada)
})

// form inertia
const form = useForm({
  details: props.minuteofmeeting?.details || "", // isi MOM kalau sudah ada
})

const saveMinute = () => {
  form.post(route("minuteofmeeting.store", { id: props.meeting.id }))
}

const exportExcel = (id) => {
  window.location.href = route("minuteofmeeting.export", id)
}

const exportPdf = (id) => {
  window.location.href = route("minuteofmeeting.export.pdf", id)
}

function formatTime(time) {
  if (!time) return null
  // Buat object tanggal fiktif biar bisa parsing jam
  const [h, m] = time.split(':')
  const date = new Date()
  date.setHours(h, m)

  return date.toLocaleTimeString('en-US', {
    hour: 'numeric',
    minute: '2-digit',
    hour12: true, // aktifkan AM/PM
  })
}

</script>

<template>
  <AuthenticatedLayout>
    <Head title="Minute of Meeting" />

    <div class="max-w-4xl mt-8 mx-auto bg-white shadow rounded-lg p-6">
      <h2 class="text-xl font-bold mb-4">
        Minute of Meeting - {{ [...new Set(props.meeting.visitors.map(v => v.company))][0] || '-' }}
      </h2>


      <!-- ✅ Info Meeting -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 text-sm">
        <div>
          <p><span class="font-semibold">Room :</span> {{ props.meeting.room?.name || "-" }}</p>
          <p><span class="font-semibold">Date :</span> {{ props.meeting.date || "-" }}</p>
          <p>
            <span class="font-semibold">Start Time :</span>
            {{ formatTime(props.meeting.start_time) || "-" }}
          </p>
          <p>
            <span class="font-semibold">End Time :</span>
            {{ formatTime(props.meeting.end_time) || "-" }}
          </p> 
          <p><span class="font-semibold">Meeting With :</span> {{ props.meeting.meeting_with?.name|| "-" }}</p>
        </div>
        <div>
          <p class="font-semibold">Visitor - Company :</p>
          <ul class="list-disc list-inside text-sm text-gray-700">
            <li v-for="(v, index) in props.meeting.visitors" :key="index">
              {{ v.name }} - {{ v.company }}  ({{ v.status }})
            </li>
          </ul>
        </div>
      </div>

      <h2 class="text-xl font-bold">Details :</h2>

      <!-- Quill Editor -->
      <QuillEditor
        theme="snow"
        v-model:content="form.details"
        contentType="html"
        class="h-64"
      />

      <div class="mt-6 flex justify-end">
        <div class="mt-6 flex justify-end gap-3">
          <!-- Tombol Simpan -->
          <button
            @click="saveMinute"
            class="flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
            :disabled="form.processing"
          >
          <Save class="w-5 h-5" />
            Save
          </button>

          <!-- Tombol Export Excel -->
          <button
            @click="exportExcel(meeting.id)"
            class="flex items-center justify-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700"
          >
          <Sheet class="w-5 h-5" />
            Export Excel
          </button>
 
                    <!-- Tombol Export PDF -->
          <button
            @click="exportPdf(meeting.id)"
            class="flex items-center justify-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700"
          >
          <FileText class="w-5 h-5" />
            Export PDF
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
