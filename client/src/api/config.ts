import type { SilverStripeConfig } from '@/types/silverstripe';
import { ConfigError } from './errors';

const CONTROLLER_FQCN =
  'WeDevelop\\ElementalGrid\\Controllers\\ElementalGridController';

/**
 * Returns the global SilverStripe CMS configuration object.
 *
 * @throws ConfigError if the admin bundle has not loaded
 */
export function getConfig(): SilverStripeConfig {
  const config = (window as Window).ss?.config;

  if (config === undefined) {
    throw new ConfigError(
      'SilverStripe config is not available. ' +
        'Ensure the admin bundle is loaded before the grid editor.',
    );
  }

  return config;
}

/**
 * Returns the CSRF security token from CMS config.
 *
 * @throws ConfigError if config is not available
 */
export function getSecurityId(): string {
  return getConfig().SecurityID;
}

/**
 * Returns the base URL for the ElementalGridController API.
 * Strips trailing slash for consistent URL construction.
 *
 * @throws ConfigError if config is not available or the controller section is missing
 */
export function getControllerLink(): string {
  const config = getConfig();

  // sections is an array of config objects; each has a `name` set to the FQCN
  const section = config.sections.find((s) => s.name === CONTROLLER_FQCN);

  if (section === undefined) {
    throw new ConfigError(
      `Controller section "${CONTROLLER_FQCN}" not found in CMS config. ` +
        'Ensure the elemental grid module is installed.',
    );
  }

  return section.controllerLink.replace(/\/+$/, '');
}
