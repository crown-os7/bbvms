<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Import ECharts
import { use } from 'echarts/core'
import { CanvasRenderer } from 'echarts/renderers'
import { PieChart, BarChart } from 'echarts/charts'
import { TitleComponent, TooltipComponent, LegendComponent, GridComponent } from 'echarts/components'
import VChart from 'vue-echarts'

// Registrasi komponen ECharts
use([CanvasRenderer, PieChart, BarChart, TitleComponent, TooltipComponent, LegendComponent, GridComponent])

// Props dari controller
const props = defineProps({
  departmentStats: {
    type: Array,
    default: () => []
  }
})

// Data dari props
const chartData = props.departmentStats.map(d => ({ name: d.department, value: d.total }))

// Opsi Bar Chart
const barOptions = {
  title: { text: 'Meeting per Departemen (Bar Chart)', left: 'center' },
  tooltip: {},
  xAxis: {
    type: 'category',
    data: props.departmentStats.map(d => d.department)
  },
  yAxis: { type: 'value' },
  series: [
    {
      data: props.departmentStats.map(d => d.total),
      type: 'bar',
      itemStyle: {
        borderRadius: [6, 6, 0, 0],
        shadowBlur: 10,
        shadowColor: 'rgba(0, 0, 0, 0.3)'
      }
    }
  ]
}

// Opsi Pie Chart (3D)
const pieOptions = {
  title: { text: 'Meeting per Departemen (Pie Chart)', left: 'center' },
  tooltip: { trigger: 'item' },
  legend: { bottom: 0 },
  series: [
    {
      name: 'Meetings',
      type: 'pie',
      roseType: 'radius',
      radius: ['30%', '70%'],
      label: { show: true, formatter: '{b}: {c}' },
      data: chartData,
      itemStyle: {
        shadowBlur: 20,
        shadowOffsetX: 5,
        shadowOffsetY: 5,
        shadowColor: 'rgba(0, 0, 0, 0.3)'
      }
    }
  ]
}
</script>

<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-xl font-semibold leading-tight text-gray-800">
        Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Card Bar Chart -->
        <div class="bg-white shadow rounded-2xl p-6 flex flex-col items-center">
          <VChart class="w-full h-80" :option="barOptions" autoresize />
        </div>

        <!-- Card Pie Chart -->
        <div class="bg-white shadow rounded-2xl p-6 flex flex-col items-center">
          <VChart class="w-full h-80" :option="pieOptions" autoresize />
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
