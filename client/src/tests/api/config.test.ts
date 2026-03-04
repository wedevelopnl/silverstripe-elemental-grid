import {
  getAdapterConfig,
  getConfig,
  getControllerLink,
  getSecurityId,
} from '@/api/config';
import { ConfigError } from '@/api/errors';
import { ZodError } from 'zod';

const CONTROLLER_FQCN =
  'WeDevelop\\ElementalGrid\\Controllers\\ElementalGridController';

const validAdapterConfig = {
  viewports: [
    { key: 'xs', label: 'Extra Small' },
    { key: 'md', label: 'Medium' },
  ],
  defaultViewport: 'md',
  columnCount: 12,
  rowClasses: 'row',
  baseWidthClasses: { '1': 'col-1', '12': 'col-12' },
  baseOffsetClasses: { '0': 'offset-0', '1': 'offset-1' },
};

function gridSection(
  controllerLink: string,
  gridAdapter?: unknown,
) {
  return {
    name: CONTROLLER_FQCN,
    url: 'admin/elemental-grid',
    controllerLink,
    ...(gridAdapter !== undefined ? { gridAdapter } : {}),
  };
}

describe('config accessors', () => {
  const originalSs = window.ss;

  afterEach(() => {
    window.ss = originalSs;
  });

  describe('getConfig', () => {
    it('returns the config object when available', () => {
      const config = {
        SecurityID: 'abc123',
        sections: [],
      };
      window.ss = { config };

      expect(getConfig()).toBe(config);
    });

    it('throws ConfigError when window.ss is missing', () => {
      // @ts-expect-error — simulating missing global
      delete window.ss;

      expect(() => getConfig()).toThrow(ConfigError);
      expect(() => getConfig()).toThrow('SilverStripe config is not available');
      expect(() => getConfig()).toThrow('admin bundle is loaded');
    });

    it('throws ConfigError when window.ss.config is undefined', () => {
      // @ts-expect-error — simulating partially loaded global
      window.ss = {};

      expect(() => getConfig()).toThrow(ConfigError);
    });
  });

  describe('getSecurityId', () => {
    it('returns the SecurityID string', () => {
      window.ss = {
        config: { SecurityID: 'token-xyz', sections: [] },
      };

      expect(getSecurityId()).toBe('token-xyz');
    });
  });

  describe('getControllerLink', () => {
    it('returns the controller link for our grid controller', () => {
      window.ss = {
        config: {
          SecurityID: 'x',
          sections: [
            { name: 'Other\\Controller', url: 'admin/other', controllerLink: '/admin/other/' },
            gridSection('/admin/elemental-grid/'),
          ],
        },
      };

      expect(getControllerLink()).toBe('/admin/elemental-grid');
    });

    it('strips trailing slashes', () => {
      window.ss = {
        config: {
          SecurityID: 'x',
          sections: [gridSection('/admin/elemental-grid///')],
        },
      };

      expect(getControllerLink()).toBe('/admin/elemental-grid');
    });

    it('throws ConfigError when section is missing', () => {
      window.ss = {
        config: { SecurityID: 'x', sections: [] },
      };

      expect(() => getControllerLink()).toThrow(ConfigError);
      expect(() => getControllerLink()).toThrow('Controller section');
      expect(() => getControllerLink()).toThrow('elemental grid module is installed');
    });
  });

  describe('getAdapterConfig', () => {
    it('returns parsed adapter config from the controller section', () => {
      window.ss = {
        config: {
          SecurityID: 'x',
          sections: [
            { name: 'Other\\Controller', url: 'admin/other', controllerLink: '/admin/other/' },
            gridSection('/admin/elemental-grid/', validAdapterConfig),
          ],
        },
      };

      const result = getAdapterConfig();
      expect(result.defaultViewport).toBe('md');
      expect(result.columnCount).toBe(12);
      expect(result.viewports).toHaveLength(2);
      expect(result.baseWidthClasses['12']).toBe('col-12');
      expect(result.baseOffsetClasses['0']).toBe('offset-0');
    });

    it('throws ConfigError when section is missing', () => {
      window.ss = {
        config: { SecurityID: 'x', sections: [] },
      };

      expect(() => getAdapterConfig()).toThrow(ConfigError);
      expect(() => getAdapterConfig()).toThrow('Controller section');
    });

    it('throws ZodError when gridAdapter is undefined', () => {
      window.ss = {
        config: {
          SecurityID: 'x',
          sections: [gridSection('/admin/elemental-grid/')],
        },
      };

      expect(() => getAdapterConfig()).toThrow(ZodError);
    });

    it('throws ZodError when gridAdapter has invalid shape', () => {
      window.ss = {
        config: {
          SecurityID: 'x',
          sections: [
            gridSection('/admin/elemental-grid/', { columnCount: 'not-a-number' }),
          ],
        },
      };

      expect(() => getAdapterConfig()).toThrow(ZodError);
    });
  });
});
