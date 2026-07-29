import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  plugins: [vue()],
  define: {
    __VUE_OPTIONS_API__: 'true',
    __VUE_PROD_DEVTOOLS__: 'false',
    __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: 'false',
  },
  build: {
    emptyOutDir: false,
    outDir: 'assets/js',
    rollupOptions: {
      input: 'src/main.js',
      output: {
        entryFileNames: 'cashier.js',
        chunkFileNames: 'cashier-[hash].js',
        assetFileNames: 'cashier-[name][extname]',
      },
    },
  },
});
