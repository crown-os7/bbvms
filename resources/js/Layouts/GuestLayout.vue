<script setup>
import { ref } from 'vue'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import { Link } from '@inertiajs/vue3'

const showingNavigationDropdown = ref(false)
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="border-b border-gray-100 bg-white">
      <!-- Primary Navigation -->
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <!-- Logo -->
          <div class="flex shrink-0 items-center">
            <Link :href="route('dashboard')">
              <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" />
            </Link>
          </div>

        <!-- Navigation Links (desktop) -->
        <div class="hidden sm:ms-10 sm:flex items-center space-x-8">
        <Link :href="route('welcome')" class="text-gray-700 hover:text-indigo-600">Home</Link>
        <!-- <Link :href="route('check-in.index')" class="text-gray-700 hover:text-indigo-600">Check-In</Link>
        <Link :href="route('check-out.index')" class="text-gray-700 hover:text-indigo-600">Check-Out</Link> -->
        <Link :href="route('login')" class="text-gray-700 hover:text-indigo-600">Login</Link>
        </div>


          <!-- Hamburger (mobile) -->
          <div class="-me-2 flex items-center sm:hidden">
            <button
              @click="showingNavigationDropdown = !showingNavigationDropdown"
              class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path
                  :class="{ hidden: showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
                <path
                  :class="{ hidden: !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Responsive Navigation Menu -->
      <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
          <Link :href="route('welcome')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Home</Link>
          <!-- <Link :href="route('visitors.checkin.form')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Check-In</Link>
          <Link :href="route('visitors.checkout.form')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Check-Out</Link> -->
          <Link :href="route('login')" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Login</Link>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>
