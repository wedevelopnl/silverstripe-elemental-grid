import { registerComponents } from './registerComponents';

document.addEventListener('DOMContentLoaded', () => {
  try {
    registerComponents();
  } catch (error: unknown) {
    console.warn('[GridEditor] Failed to register components.', error);
  }
});
