import { getInjector } from '@/bridge/Injector';
import GridEditor from '@/components/GridEditor/GridEditor';

/**
 * Register all grid editor components with the SilverStripe Injector.
 * Components registered here can be retrieved (and transformed) via
 * `Injector.loadComponent('GridEditor')`.
 */
export function registerComponents(): void {
  getInjector().component.registerMany({
    GridEditor,
  });
}
