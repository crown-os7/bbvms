<script setup>
import { ref, watch, onMounted, computed } from "vue";
import { useForm, Head, router } from "@inertiajs/vue3";
import axios from "axios";
import { LogOut, Home, ArrowLeft, Check, ChevronDown } from "lucide-vue-next";

import {
  Combobox,
  ComboboxInput,
  ComboboxButton,
  ComboboxOptions,
  ComboboxOption,
  TransitionRoot,
} from "@headlessui/vue";

// ✅ form data
const form = useForm({
  referral_code: "",
  visitor_id: null,
});

const visitors = ref([]);
const message = ref(null);
const messageType = ref("");
const loadingVisitor = ref(false);
const query = ref("");

// ✅ ambil referral_code dari URL query param saat halaman pertama kali dibuka
onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const refCode = params.get("ref");
  if (refCode) {
    form.referral_code = refCode;
  }
});

// ✅ ambil daftar visitor saat referral_code berubah
watch(
  () => form.referral_code,
  async (val) => {
    form.visitor_id = null;
    visitors.value = [];

    if (!val) return;

    loadingVisitor.value = true;
    try {
      const res = await axios.get(`/visitors/by-referral`, {
        params: { referral_code: val },
      });
      visitors.value = res.data.visitors || [];
    } catch (err) {
      console.error(err);
      visitors.value = [];
    } finally {
      loadingVisitor.value = false;
    }
  }
);

// ✅ reset form
const resetForm = () => {
  form.reset("referral_code");
  form.visitor_id = null;
  visitors.value = [];
};

// ✅ submit checkout
const submit = () => {
  if (!form.visitor_id) {
    alert("Select a visitor first!");
    return;
  }

  form.post(route("visitors.checkout"), {
    data: {
      referral_code: form.referral_code,
      visitor_id: form.visitor_id,
    },
    onSuccess: () => {
      message.value = "Checkout berhasil ✅";
      messageType.value = "success";
      resetForm();
    },
    onError: (err) => {
      console.error(err);
      message.value = "Checkout gagal ❌";
      messageType.value = "error";
    },
  });
};

// ✅ filter visitors (search di combobox)
const filteredVisitors = computed(() =>
  query.value === ""
    ? visitors.value
    : visitors.value.filter((v) =>
        v.name.toLowerCase().includes(query.value.toLowerCase())
      )
);
</script>

<template>
  <Head title="Check-Out" />

  <div class="min-h-screen bg-gray-100 flex flex-col">
    <!-- ✅ Header Home & Back di atas -->
    <div class="flex justify-between items-center w-full px-6 py-4">
      <button
        @click="router.visit('/')"
        class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md transition"
      >
        <Home class="w-5 h-5" /> Home
      </button>

      <button
        @click="router.visit(route('check-out.index'))"
        class="flex items-center gap-2 bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md transition"
      >
        <ArrowLeft class="w-5 h-5" /> Back
      </button>
    </div>

    <!-- ✅ Card di tengah -->
    <div class="flex justify-center items-center flex-1">
      <div class="bg-white p-8 rounded-xl shadow-md w-full max-w-2xl">
        <h2 class="text-2xl font-semibold mb-6 text-center">
          Visitor Check-Out
        </h2>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Referral Code -->
          <div>
            <label for="referral_code" class="block text-gray-700 mb-2">
              Referral Code
            </label>
            <input
              v-model="form.referral_code"
              id="referral_code"
              placeholder="Masukkan kode referral"
              type="text"
              maxlength="6"
              class="w-full border rounded-lg px-3 py-2 focus:ring focus:ring-blue-300"
              required
            />
          </div>

          <!-- Select Visitor (Combobox) -->
          <div>
            <label class="block text-gray-700 mb-2">Select Visitor</label>
            <Combobox v-model="form.visitor_id">
              <div class="relative">
                <ComboboxInput
                  class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2"
                  placeholder="Search visitor..."
                  @change="query = $event.target.value"
                  :displayValue="id => visitors.find(v => v.id == id)?.name || ''"
                  :disabled="loadingVisitor || visitors.length === 0"
                />
                <ComboboxButton
                  class="absolute inset-y-0 right-0 flex items-center pr-2"
                  @click="query = ''"
                >
                  <ChevronDown class="w-5 h-5 text-gray-400" />
                </ComboboxButton>

                <TransitionRoot
                  leave="transition ease-in duration-100"
                  leave-from="opacity-100"
                  leave-to="opacity-0"
                >
                  <ComboboxOptions
                    class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-lg bg-white shadow-lg border focus:outline-none"
                  >
                    <div v-if="filteredVisitors.length === 0" class="p-2 text-gray-500">
                      No visitors found
                    </div>
                    <ComboboxOption
                      v-for="v in filteredVisitors"
                      :key="v.id"
                      :value="v.id"
                      v-slot="{ selected, active }"
                    >
                      <div
                        :class="[
                          'cursor-pointer select-none relative p-2',
                          active ? 'bg-blue-600 text-white' : 'text-gray-900'
                        ]"
                      >
                        <span
                          :class="[
                            'block truncate',
                            selected ? 'font-semibold' : 'font-normal'
                          ]"
                        >
                          {{ v.name }} - {{ v.company }}
                        </span>
                        <span
                          v-if="selected"
                          class="absolute inset-y-0 right-0 flex items-center pr-3 text-blue-600"
                        >
                          <Check class="w-5 h-5" />
                        </span>
                      </div>
                    </ComboboxOption>
                  </ComboboxOptions>
                </TransitionRoot>
              </div>
            </Combobox>
          </div>

          <!-- Button -->
          <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition"
            :disabled="form.processing || loadingVisitor"
          >
            <LogOut class="w-5 h-5" />
            Check-Out
          </button>
        </form>

        <!-- Message -->
        <div v-if="message" class="mt-4 text-center">
          <p
            :class="messageType === 'success' ? 'text-green-600' : 'text-red-600'"
          >
            {{ message }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
