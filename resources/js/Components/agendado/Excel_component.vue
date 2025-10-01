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
        ref="fileInput"
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
      
      <!-- Instrucciones mejoradas -->
      <div class="mt-2 p-2 bg-blue-100 dark:bg-blue-800 rounded text-xs">
        <p class="font-semibold text-blue-900 dark:text-blue-100 mb-1">📋 Formato Requerido:</p>
        <p class="text-blue-800 dark:text-blue-200 mb-2">
          Tu archivo Excel debe tener <strong>exactamente</strong> estas 2 columnas:
        </p>
        <ul class="list-disc list-inside text-blue-800 dark:text-blue-200 space-y-1 mb-2">
          <li><code class="bg-white dark:bg-gray-700 px-1 rounded">start_datetime</code></li>
          <li><code class="bg-white dark:bg-gray-700 px-1 rounded">end_datetime</code></li>
        </ul>
        <p class="text-blue-800 dark:text-blue-200">
          <strong>Formatos aceptados:</strong> YYYY-MM-DD HH:MM:SS o DD/MM/YYYY HH:MM
        </p>
      </div>
      
      <!-- Imagen de ejemplo -->
      <div class="mt-2 border-2 border-dashed border-blue-300 dark:border-blue-600 rounded p-2">
        <p class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-1">✨ Ejemplo correcto:</p>
        <div class="bg-white dark:bg-gray-700 rounded overflow-hidden">
          <table class="w-full text-xs">
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-2 py-1 text-left border border-blue-700">start_datetime</th>
                <th class="px-2 py-1 text-left border border-blue-700">end_datetime</th>
              </tr>
            </thead>
            <tbody class="text-gray-800 dark:text-gray-200">
              <tr>
                <td class="px-2 py-1 border border-gray-300">2025-10-15 09:00:00</td>
                <td class="px-2 py-1 border border-gray-300">2025-10-15 11:00:00</td>
              </tr>
              <tr class="bg-gray-50 dark:bg-gray-600">
                <td class="px-2 py-1 border border-gray-300">15/10/2025 14:00</td>
                <td class="px-2 py-1 border border-gray-300">15/10/2025 16:00</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Messages con botón para cerrar -->
      <div v-if="importMessage" class="mt-2 p-2 bg-green-100 dark:bg-green-900 rounded text-xs text-green-800 dark:text-green-200 flex items-start justify-between">
        <span>{{ importMessage }}</span>
        <button @click="clearMessages" class="ml-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 font-bold">
          ✕
        </button>
      </div>
      <div v-if="importError" class="mt-2 p-2 bg-red-100 dark:bg-red-900 rounded text-xs text-red-800 dark:text-red-200">
        <div class="flex items-start justify-between mb-2">
          <span class="font-semibold">⚠️ Error al importar</span>
          <button @click="clearMessages" class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 font-bold">
            ✕
          </button>
        </div>
        <pre class="whitespace-pre-wrap text-xs">{{ importError }}</pre>
        <button 
          @click="clearMessages" 
          class="mt-2 px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs"
        >
          Intentar de nuevo
        </button>
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
    resetInput() {
      // Resetear el input file para permitir subir el mismo archivo nuevamente
      const fileInput = this.$refs.fileInput;
      if (fileInput) {
        fileInput.value = '';
      }
    },

    clearMessages() {
      this.importMessage = null;
      this.importError = null;
    },

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

      // Limpiar mensajes previos
      this.clearMessages();

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
              const foundColumns = Object.keys(firstRow).join(', ');
              throw new Error(
                `❌ Error en el formato del archivo\n\n` +
                `Se requieren las columnas: start_datetime, end_datetime\n` +
                `Se encontraron: ${foundColumns}\n\n` +
                `Por favor verifica que los nombres de las columnas sean exactos (incluyendo minúsculas/mayúsculas).`
              );
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
                throw new Error(
                  `❌ Error en fila ${i + 2}\n\n` +
                  `Formato de fecha/hora inválido:\n` +
                  `- start_datetime: "${row.start_datetime}"\n` +
                  `- end_datetime: "${row.end_datetime}"\n\n` +
                  `Usa uno de estos formatos:\n` +
                  `• YYYY-MM-DD HH:MM:SS (ej: 2025-10-15 09:00:00)\n` +
                  `• DD/MM/YYYY HH:MM (ej: 15/10/2025 09:00)`
                );
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
            
            // Resetear input y limpiar mensaje después de 5 segundos
            this.resetInput();
            setTimeout(() => {
              this.importMessage = null;
            }, 5000);

          } catch (parseError) {
            console.error('Error al procesar Excel:', parseError);
            this.importError = parseError.message;
            // Resetear el input para permitir reintentar
            this.resetInput();
          }
        };

        reader.onerror = () => {
          this.importError = 'Error al leer el archivo. Intente nuevamente.';
          this.resetInput();
        };

        reader.readAsArrayBuffer(file);
        
      } catch (error) {
        console.error('Error importing Excel:', error);
        this.importError = `Error al importar el archivo Excel: ${error.message}`;
        this.resetInput();
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