<script>

import * as XLSX from 'xlsx';

export default {
  // PROPS: Datos que recibe del componente padre
  props: {
    create_events: {
      type: Array,
      default: () => [],  // Lista de eventos existentes en el calendario
    },
  },
  
  //   Eventos que este componente puede emitir al padre
  emits: ['update:create_events', 'edit-event', 'delete-event'],
  
  //  DATA: Variables internas del componente
  data() {
    return {
      importMessage: null,  // Almacena el mensaje de éxito
      importError: null,    // Almacena el mensaje de error
    };
  },
  
  //   Funciones del componente
  methods: {
    /**
     * 
     * Limpia el valor del input file para permitir subir el mismo archivo nuevamente
     * Sin esto, si subes el mismo archivo dos veces, el evento @change no se dispara
     */
    resetInput() {
      const fileInput = this.$refs.fileInput;
      if (fileInput) {
        fileInput.value = '';
      }
    },

    /**Borra los mensajes de éxito y error de la pantalla*/
    clearMessages() {
      this.importMessage = null;
      this.importError = null;
    },

    /**
     *
     * Crea un texto resumen del evento para mostrar en la UI
     * Ejemplo: "2025-10-15 09:00-11:00 (3 fechas)"
     * 
     */
    formatEventSummary(eventObj, index) {
      const event = eventObj[`event_${index + 1}`];
      if (!event) return 'Evento inválido';
      
      // Extraer partes de la fecha
      const date = event.start_datetime?.split(' ')[0] || 'N/A';
      const startTime = event.start_datetime?.split(' ')[1]?.substring(0, 5) || 'N/A';
      const endTime = event.end_datetime?.split(' ')[1]?.substring(0, 5) || 'N/A';
      
      // Contar cuántas fechas irregulares tiene
      const irregularCount = event.irregular_dates ? JSON.parse(event.irregular_dates).length : 0;
      
      return `${date} ${startTime}-${endTime}${irregularCount > 1 ? ` (${irregularCount} fechas)` : ''}`;
    },

    /**
     * MANEJAR IMPORTACIÓN DE EXCEL
     * Función principal que procesa el archivo Excel y convierte las filas en eventos
     * 
     * FLUJO:
     * 1. Leer el archivo
     * 2. Convertir Excel a JSON
     * 3. Validar estructura (columnas requeridas)
     * 4. Validar cada fila (formato de fechas)
     * 5. Crear objetos de eventos
     * 6. Enviar eventos al componente padre
     * 7. Mostrar mensaje de éxito o error
     * 
     * @param {Event} event - Evento del input file
     */
    async handleExcelImport(event) {
      // Obtener el archivo seleccionado por el usuario
      const file = event.target.files[0];
      if (!file) return;  // Si no hay archivo, salir

      // Limpiar mensajes anteriores
      this.clearMessages();

      try {
        //  Crear un lector de archivos
        const reader = new FileReader();
        
        // Definir qué hacer cuando el archivo se haya leído
        reader.onload = (e) => {
          try {
            //  Convertir el archivo Excel a datos JSON
            const data = new Uint8Array(e.target.result);  // Obtener datos binarios
            const workbook = XLSX.read(data, { type: 'array' });  // Leer el libro de Excel
            const firstSheet = workbook.Sheets[workbook.SheetNames[0]];  // Obtener la primera hoja
            const jsonData = XLSX.utils.sheet_to_json(firstSheet);  // Convertir hoja a JSON

            console.log('Datos leídos del Excel:', jsonData);

            // Verificar que el Excel no esté vacío
            if (jsonData.length === 0) {
              throw new Error('El archivo Excel está vacío o no tiene datos válidos.');
            }

            //  Verificar que tenga las columnas requeridas
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

            //  Procesar cada fila del Excel
            const newEvents = [];
            for (let i = 0; i < jsonData.length; i++) {
              const row = jsonData[i];
              
              //  Verificar que la fila tenga ambas fechas
              if (!row.start_datetime || !row.end_datetime) {
                throw new Error(`Fila ${i + 2}: start_datetime y end_datetime son requeridos`);
              }

              //  Validar y procesar el formato de las fechas
              const processedStartDateTime = this.isValidDateTime(row.start_datetime);
              const processedEndDateTime = this.isValidDateTime(row.end_datetime);
              
              //  Verificar que las fechas tengan un formato válido
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

              //  Extraer las partes de la fecha y hora
              const startDate = processedStartDateTime.split(' ')[0];  // "2025-10-15"
              const startTime = processedStartDateTime.split(' ')[1]?.substring(0, 5) || '09:00';  // "09:00"
              const endTime = processedEndDateTime.split(' ')[1]?.substring(0, 5) || '10:00';  // "10:00"

              //  Crear el objeto de fecha irregular
              // Este formato es específico del sistema de calendario del proyecto
              const irregularDates = [{
                date: startDate,
                startTime: startTime,
                endTime: endTime,
                displayText: `${startDate} ${startTime} - ${endTime}`
              }];

              //  Crear el objeto del evento completo
              const newEvent = {
                start_datetime: `${startDate} ${startTime}:00`,  // Fecha de inicio con segundos
                end_datetime: `${startDate} ${endTime}:00`,      // Fecha de fin con segundos
                recurring_days: null,        // No es un evento recurrente
                recurring_frequency: null,   // No tiene frecuencia de repetición
                irregular_dates: JSON.stringify(irregularDates),  // Fechas irregulares en JSON
                repeticion: null,            // Sin repetición
                has_conflicts: false,        // Sin conflictos (se verificará después)
              };

              newEvents.push(newEvent);  // Agregar evento a la lista
            }

            // Combinar eventos nuevos con eventos existentes
            let updatedEvents = [...this.create_events];  // Copiar eventos existentes
            newEvents.forEach((newEvent, idx) => {
              // Cada evento se guarda con una clave única: event_1, event_2, etc.
              const eventKey = `event_${updatedEvents.length + 1}`;
              updatedEvents.push({ [eventKey]: newEvent });
            });

            console.log('Eventos creados desde Excel:', newEvents);

            //  Enviar los eventos actualizados al componente padre
            this.$emit('update:create_events', updatedEvents);
            
            //  Mostrar mensaje de éxito
            this.importMessage = `✅ Se importaron ${jsonData.length} fechas exitosamente.`;
            
            //  Limpiar el input y ocultar mensaje después de 5 segundos
            this.resetInput();
            setTimeout(() => {
              this.importMessage = null;
            }, 5000);

          } catch (parseError) {
            //  Si hay error al procesar el Excel, mostrarlo
            console.error('Error al procesar Excel:', parseError);
            this.importError = parseError.message;
            this.resetInput();
          }
        };

        //  Manejar errores al leer el archivo
        reader.onerror = () => {
          this.importError = 'Error al leer el archivo. Intente nuevamente.';
          this.resetInput();
        };

        //  Iniciar la lectura del archivo
        reader.readAsArrayBuffer(file);
        
      } catch (error) {
        //  Capturar cualquier otro error inesperado
        console.error('Error importing Excel:', error);
        this.importError = `Error al importar el archivo Excel: ${error.message}`;
        this.resetInput();
      }
    },

    /**
     * VALIDAR Y CONVERTIR FECHA/HORA
     * Función que acepta múltiples formatos de fecha/hora y los convierte a un formato estándar
     * 
     * FORMATOS ACEPTADOS:
     * - "2025-10-15 09:00:00" (ISO con segundos)
     * - "2025-10-15 09:00" (ISO sin segundos)
     * - "2025-10-15T09:00:00" (ISO con T)
     * - "2025-10-15" (Solo fecha)
     * - "15/10/2025 09:00" (Formato español)
     * - 44556 (Número serial de Excel)
     * 
     * @param {String|Number} dateTimeString - Fecha en cualquier formato
     * @returns {String|Boolean} Fecha en formato "YYYY-MM-DD HH:MM:SS" o false si es inválida
     */
    isValidDateTime(dateTimeString) {
      if (!dateTimeString) return false;
      
      let dateStr = dateTimeString.toString().trim();
      
      //   Si es un número (formato serial de Excel)
      // Excel guarda las fechas como números: 44556 = 2025-10-15
      if (!isNaN(dateTimeString) && typeof dateTimeString === 'number') {
        // Fórmula de conversión de Excel a JavaScript
        // 25569 es la diferencia entre el epoch de Excel (1900) y JavaScript (1970)
        const excelDate = new Date((dateTimeString - 25569) * 86400 * 1000);
        dateStr = excelDate.toISOString().replace('T', ' ').substring(0, 19);
      }
      
      let processedDate;
      
      // Formato ISO completo "2025-10-15 09:00:00"
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(dateStr)) {
        processedDate = dateStr;  // Ya está en el formato correcto
      }
      // Formato ISO sin segundos "2025-10-15 09:00"
      else if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(dateStr)) {
        processedDate = dateStr + ':00';  // Agregar :00 al final
      }
      // Formato ISO con T "2025-10-15T09:00:00"
      else if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(dateStr)) {
        processedDate = dateStr.replace('T', ' ').substring(0, 19);  // Reemplazar T por espacio
      }
      //  Solo fecha "2025-10-15"
      else if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        processedDate = dateStr + ' 09:00:00';  // Agregar hora por defecto
      }
      //  Formato español "15/10/2025 09:00"
      else if (/^\d{1,2}\/\d{1,2}\/\d{4}/.test(dateStr)) {
        try {
          const parts = dateStr.split(' ');
          const datePart = parts[0];  // "15/10/2025"
          let timePart = parts[1] || '09:00';  // "09:00" o hora por defecto
          
          // Si la hora no tiene segundos, agregarlos
          if (timePart.split(':').length === 2) {
            timePart += ':00';
          }
          
          // Separar día, mes y año
          const [day, month, year] = datePart.split('/');
          
          // Convertir a formato ISO: YYYY-MM-DD HH:MM:SS
          // padStart(2, '0') agrega un 0 al inicio si el número tiene un solo dígito
          processedDate = `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')} ${timePart}`;
        } catch (e) {
          return false;  // Si hay error al parsear, retornar false
        }
      }
      //  Formato no reconocido
      else {
        return false;
      }
      
      // Verificar que la fecha sea válida
      const date = new Date(processedDate);
      return !isNaN(date.getTime()) ? processedDate : false;
    },

    /**
     * 
     *  valida solo fechas en formato YYYY-MM-DD
     * Se mantiene por compatibilidad pero no se utiliza en el flujo actual
     * 
     * @param {String} dateString - Fecha en formato YYYY-MM-DD
     * @returns {Boolean} true si es válida, false si no
     */
    isValidDate(dateString) {
      const regex = /^\d{4}-\d{2}-\d{2}$/;
      if (!regex.test(dateString)) return false;
      
      const date = new Date(dateString);
      return !isNaN(date.getTime());
    },
  },
};
</script>

<template>
 
  <div class="w-full bg-white dark:bg-gray-800 p-3 rounded-lg shadow-md">
    
    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-200 mb-2">Importar desde Excel</h3>
    
    
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
      
      <!-- TABLA DE EJEMPLO: Muestra visualmente cómo debe verse el Excel -->
      <div class="mt-2 border-2 border-dashed border-blue-300 dark:border-blue-600 rounded p-2">
        <p class="text-xs font-semibold text-blue-900 dark:text-blue-100 mb-1">✨ Ejemplo correcto:</p>
        <div class="bg-white dark:bg-gray-700 rounded overflow-hidden">
          <table class="w-full text-xs">
            <!-- Encabezados de la tabla -->
            <thead class="bg-blue-600 text-white">
              <tr>
                <th class="px-2 py-1 text-left border border-blue-700">start_datetime</th>
                <th class="px-2 py-1 text-left border border-blue-700">end_datetime</th>
              </tr>
            </thead>
            <!-- Filas de ejemplo -->
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
      
      <!--  MENSAJE DE ÉXITO -->
      <div v-if="importMessage" class="mt-2 p-2 bg-green-100 dark:bg-green-900 rounded text-xs text-green-800 dark:text-green-200 flex items-start justify-between">
        <span>{{ importMessage }}</span>
        <!-- Botón para cerrar el mensaje -->
        <button @click="clearMessages" class="ml-2 text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 font-bold">
          ✕
        </button>
      </div>
      
      <!--  MENSAJE DE ERROR -->
      <div v-if="importError" class="mt-2 p-2 bg-red-100 dark:bg-red-900 rounded text-xs text-red-800 dark:text-red-200">
        <div class="flex items-start justify-between mb-2">
          <span class="font-semibold">⚠️ Error al importar</span>
          <!-- Botón para cerrar el mensaje de error -->
          <button @click="clearMessages" class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 font-bold">
            ✕
          </button>
        </div>
        <!-- Mostrar el detalle del error -->
        <pre class="whitespace-pre-wrap text-xs">{{ importError }}</pre>
        <!-- Botón para intentar de nuevo -->
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

