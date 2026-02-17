/// <reference types="vitest/config" />
import { resolve } from 'node:path';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'client/src'),
    },
  },
  build: {
    outDir: 'client/dist',
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['client/src/**/*.{test,spec}.{ts,tsx}'],
    css: true,
  },
});
