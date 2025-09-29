<!-- Excel_component.vue (versión compacta) -->
<template>
  <div class="w-full bg-white dark:bg-gray-800 p-3 rounded-lg shadow-md">
    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-200 mb-2">Importar desde Excel</h3>
    
    <!-- Excel Import Section -->
    <div class="p-3 bg-blue-50 dark:bg-blue-900 rounded-md">
      <label for="excel-upload" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
        Subir archivo Excel
      </label>
      <input
        id="excel-upload"
        type="file"
        accept=".xlsx, .xls"
        @change="handleExcelImport"
        class="mt-1 block w-full text-xs text-gray-500
               file:mr-2 file:py-1 file:px-3
               file:rounded-md file:border-0
               file:text-xs file:font-semibold
               file:bg-blue-50 file:text-blue-700
               hover:file:bg-blue-100"
      />
      <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
        <strong>Formato:</strong> start_datetime y end_datetime (YYYY-MM-DD HH:MM:SS o DD/MM/YYYY HH:MM)
      </p>
      
      <!-- Messages -->
      <div v-if="importMessage" class="mt-2 p-2 bg-green-100 dark:bg-green-900 rounded text-xs text-green-800 dark:text-green-200">
        {{ importMessage }}
      </div>
      <div v-if="importError" class="mt-2 p-2 bg-red-100 dark:bg-red-900 rounded text-xs text-red-800 dark:text-red-200">
        {{ importError }}
      </div>
    </div>
  </div>
</template>

<script>
import * as XLSX from 'xlsx';

export default {
  props: {
    create_events: {
      type: Array,
      default: () => [],
    },
  },
  emits: ['update:create_events', 'edit-event', 'delete-event'],
  data() {
    return {
      importMessage: null,
      importError: null,
    };
  },
  methods: {
    formatEventSummary(eventObj, index) {
      const event = eventObj[`event_${index + 1}`];
      if (!event) return 'Evento inválido';
      
      const date = event.start_datetime?.split(' ')[0] || 'N/A';
      const startTime = event.start_datetime?.split(' ')[1]?.substring(0, 5) || 'N/A';
      const endTime = event.end_datetime?.split(' ')[1]?.substring(0, 5) || 'N/A';
      
      const irregularCount = event.irregular_dates ? JSON.parse(event.irregular_dates).length : 0;
      
      return `${date} ${startTime}-${endTime}${irregularCount > 1 ? ` (${irregularCount} fechas)` : ''}`;
    },

    async handleExcelImport(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.importMessage = null;
      this.importError = null;

      try {
        const reader = new FileReader();
        reader.onload = (e) => {
          try {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
            const jsonData = XLSX.utils.sheet_to_json(firstSheet);

            console.log('Datos leídos del Excel:', jsonData);

            if (jsonData.length === 0) {
              throw new Error('El archivo Excel está vacío o no tiene datos válidos.');
            }

            const requiredColumns = ['start_datetime', 'end_datetime'];
            const firstRow = jsonData[0];
            const hasRequiredColumns = requiredColumns.every(col => col in firstRow);
            
            if (!hasRequiredColumns) {
              console.log('Columnas encontradas:', Object.keys(firstRow));
              throw new Error(`El archivo debe contener las columnas: ${requiredColumns.join(', ')}. Columnas encontradas: ${Object.keys(firstRow).join(', ')}`);
            }

            const newEvents = [];
            for (let i = 0; i < jsonData.length; i++) {
              const row = jsonData[i];
              
              if (!row.start_datetime || !row.end_datetime) {
                throw new Error(`Fila ${i + 2}: start_datetime y end_datetime son requeridos`);
              }

              const processedStartDateTime = this.isValidDateTime(row.start_datetime);
              const processedEndDateTime = this.isValidDateTime(row.end_datetime);
              
              if (!processedStartDateTime || !processedEndDateTime) {
                throw new Error(`Fila ${i + 2}: Formato de fecha/hora inválido. Recibido: start="${row.start_datetime}", end="${row.end_datetime}". Use formato: YYYY-MM-DD HH:MM:SS o DD/MM/YYYY HH:MM`);
              }

              const startDate = processedStartDateTime.split(' ')[0];
              const startTime = processedStartDateTime.split(' ')[1]?.substring(0, 5) || '09:00';
              const endTime = processedEndDateTime.split(' ')[1]?.substring(0, 5) || '10:00';

              const irregularDates = [{
                date: startDate,
                startTime: startTime,
                endTime: endTime,
                displayText: `${startDate} ${startTime} - ${endTime}`
              }];

              const newEvent = {
                start_datetime: `${startDate} ${startTime}:00`,
                end_datetime: `${startDate} ${endTime}:00`,
                recurring_days: null,
                recurring_frequency: null,
                irregular_dates: JSON.stringify(irregularDates),
                repeticion: null,
                has_conflicts: false,
              };

              newEvents.push(newEvent);
            }

            let updatedEvents = [...this.create_events];
            newEvents.forEach((newEvent, idx) => {
              const eventKey = `event_${updatedEvents.length + 1}`;
              updatedEvents.push({ [eventKey]: newEvent });
            });

            console.log('Eventos creados desde Excel:', newEvents);

            this.$emit('update:create_events', updatedEvents);
            
            this.importMessage = `✅ Se importaron ${jsonData.length} fechas exitosamente.`;
            event.target.value = '';
            
            setTimeout(() => {
              this.importMessage = null;
            }, 5000);

          } catch (parseError) {
            console.error('Error al procesar Excel:', parseError);
            this.importError = `Error al procesar el archivo: ${parseError.message}`;
          }
        };

        reader.onerror = () => {
          this.importError = 'Error al leer el archivo. Intente nuevamente.';
        };

        reader.readAsArrayBuffer(file);
        
      } catch (error) {
        console.error('Error importing Excel:', error);
        this.importError = `Error al importar el archivo Excel: ${error.message}`;
      }
    },

    isValidDateTime(dateTimeString) {
      if (!dateTimeString) return false;
      
      let dateStr = dateTimeString.toString().trim();
      
      if (!isNaN(dateTimeString) && typeof dateTimeString === 'number') {
        const excelDate = new Date((dateTimeString - 25569) * 86400 * 1000);
        dateStr = excelDate.toISOString().replace('T', ' ').substring(0, 19);
      }
      
      let processedDate;
      
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateStr)) {
        processedDate = dateStr;
      }
      else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(dateStr)) {
        processedDate = dateStr + ':00';
      }
      else if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(dateStr)) {
        processedDate = dateStr.replace('T', ' ').substring(0, 19);
      }
      else if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        processedDate = dateStr + ' 09:00:00';
      }
      else if (/^\d{1,2}\/\d{1,2}\/\d{4}/.test(dateStr)) {
        try {
          const parts = dateStr.split(' ');
          const datePart = parts[0];
          let timePart = parts[1] || '09:00';
          
          if (timePart.split(':').length === 2) {
            timePart += ':00';
          }
          
          const [day, month, year] = datePart.split('/');
          processedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${timePart}`;
        } catch (e) {
          return false;
        }
      }
      else {
        return false;
      }
      
      const date = new Date(processedDate);
      return !isNaN(date.getTime()) ? processedDate : false;
    },

    isValidDate(dateString) {
      const regex = /^\d{4}-\d{2}-\d{2}$/;
      if (!regex.test(dateString)) return false;
      
      const date = new Date(dateString);
      return !isNaN(date.getTime());
    },
  },
};
</script>