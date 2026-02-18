import type { InjectorContainer } from '@/types/silverstripe';

/**
 * Returns the SilverStripe Injector Container singleton.
 * Used to register and retrieve components in the CMS.
 *
 * @throws TypeError if the Injector global is not available (admin bundle not loaded)
 */
export function getInjector(): InjectorContainer {
  if (window.Injector?.default === undefined) {
    throw new TypeError(
      'SilverStripe Injector is not available. ' +
        'Ensure the admin bundle is loaded before the grid editor.',
    );
  }

  return window.Injector.default;
}

/**
 * Load a component from the Injector with all registered transforms applied.
 *
 * @throws TypeError if the Injector global is not available
 */
export function loadComponent(
  name: string,
  context?: Record<string, unknown>,
): ReturnType<typeof window.Injector.loadComponent> {
  if (typeof window.Injector?.loadComponent !== 'function') {
    throw new TypeError(
      'SilverStripe Injector is not available. ' +
        'Ensure the admin bundle is loaded before the grid editor.',
    );
  }

  return window.Injector.loadComponent(name, context);
}
