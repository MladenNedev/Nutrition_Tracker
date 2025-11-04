// vite.config.js
import { defineConfig } from 'vite';
import path from 'path';

export default defineConfig({
  // project root 
  root: '.',
  build: {
    // where the compiled files go
    // -> root/web/public/assets/...
    outDir: 'web/public/assets',
    emptyOutDir: false,
    rollupOptions: {
      // ENTRY FILE is in root/assets/js/app.js
      input: path.resolve(process.cwd(), 'assets/js/app.js'),
      output: {
        // final JS → web/public/assets/js/app.js
        entryFileNames: 'js/app.js',
        // final CSS → web/public/assets/css/app.css
        assetFileNames: (assetInfo) => {
          if (assetInfo.name && assetInfo.name.endsWith('.css')) {
            return 'css/app.css';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
  css: {
    preprocessorOptions: {
      scss: {},
    },
  },
});
