import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
   /* server: {
        watch: {
           usePolling: true,
          interval: 500,
          
        },
        host: 'agenda.dgre.mx',
        hmr: {
            host: 'agenda.dgre.mx',
        },
    },*/
    server: {
        host: '0.0.0.0', // Permite conexiones externas
        port: 5174, // Usa el puerto que muestra el error
        strictPort: true, // Evita que cambie de puerto
        cors: true, // Habilita CORS
        hmr: {
            host: 'agenda.dgre.mx', // Para Hot Module Reload (HMR)
            protocol: 'ws',
        },
    },
});
