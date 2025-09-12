<template>
    <!-- Calendar Component -->
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
                     <div class="flex justify-between items-center mb-4">
                       <button @click="prevMonth" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                         </svg>
                       </button>
                       <h3 class="text-lg font-medium">{{ currentMonthName }} {{ currentYear }}</h3>
                       <button @click="nextMonth" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-600">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                         </svg>
                       </button>
                     </div>
                     
                     <div class="grid grid-cols-7 gap-1 mb-2">
                       <div v-for="day in ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb']" 
                            :key="day" 
                            class="text-center font-medium text-sm text-gray-500 dark:text-gray-400">
                         {{ day }}
                       </div>
                     </div>
                     
                     <div class="grid grid-cols-7 gap-1">
                       <div v-for="day in daysInMonth" 
                            :key="day.date"
                            @click="selectDate(day)"
                            class="h-10 flex items-center justify-center rounded-full cursor-pointer text-sm"
                            :class="{
                              'text-gray-400 dark:text-gray-500': !day.isCurrentMonth,
                              'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200': isDateSelected(day.date),
                              'hover:bg-gray-200 dark:hover:bg-gray-600': day.isCurrentMonth && !isDateSelected(day.date),
                              'font-bold': day.isToday
                            }">
                         {{ day.day }}
                       </div>
                     </div>
                   </div>
                   
                   <!-- Selected Dates List -->
                   <div v-if="selectedDates.length > 0" class="mb-6">
                     <h3 class="text-lg font-medium mb-3 text-gray-700 dark:text-gray-300">Fechas Seleccionadas</h3>
                     <div class="space-y-2">
                       <div v-for="(date, index) in selectedDates" 
                            :key="index"
                            class="flex items-center justify-between p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                         <div>
                           <span class="font-medium">{{ formatDate(date.start_date) }}</span>
                           <span v-if="date.start_date !== date.end_date"> - {{ formatDate(date.end_date) }}</span>
                           <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">
                             {{ date.start_time }} - {{ date.end_time }}
                           </span>
                           <span v-if="date.is_recurring" class="ml-2 text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 px-2 py-1 rounded">
                             {{ getRecurrenceLabel(date) }}
                           </span>
                         </div>
                         <div class="flex space-x-2">
                           <button @click="openTimeModal(index)" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                             </svg>
                           </button>
                           <button @click="removeDate(index)" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                             </svg>
                           </button>
                         </div>
                       </div>
                     </div>
                   </div>
 </template>
 
 <script setup>
 defineProps({
   form: Object,
   errors: Object
 });
 </script>
 