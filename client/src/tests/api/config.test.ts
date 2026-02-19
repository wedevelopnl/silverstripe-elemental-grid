import { getConfig, getControllerLink, getSecurityId } from '@/api/config';
import { ConfigError } from '@/api/errors';

const CONTROLLER_FQCN =
  'WeDevelop\\ElementalGrid\\Controllers\\ElementalGridController';

function gridSection(controllerLink: string) {
  return {
    name: CONTROLLER_FQCN,
    url: 'admin/elemental-grid',
    controllerLink,
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
          sections: [gridSection('/admin/elemental-grid/')],
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
    });
  });
});
