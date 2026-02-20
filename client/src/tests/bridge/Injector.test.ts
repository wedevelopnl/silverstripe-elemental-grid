import { getInjector, loadComponent } from '@/bridge/Injector';

describe('Injector bridge', () => {
  const originalInjector = window.Injector;

  afterEach(() => {
    window.Injector = originalInjector;
  });

  describe('getInjector', () => {
    it('returns the Container singleton when available', () => {
      const mockContainer = { component: { registerMany: vi.fn() } };
      window.Injector = {
        default: mockContainer,
        loadComponent: vi.fn(),
      };

      expect(getInjector()).toBe(mockContainer);
    });

    it('throws TypeError when Injector global is missing', () => {
      // @ts-expect-error — simulating missing global
      delete window.Injector;

      expect(() => getInjector()).toThrow(TypeError);
      expect(() => getInjector()).toThrow('SilverStripe Injector is not available');
      expect(() => getInjector()).toThrow('admin bundle is loaded');
    });

    it('throws TypeError when Injector.default is undefined', () => {
      // @ts-expect-error — simulating partially loaded global
      window.Injector = {};

      expect(() => getInjector()).toThrow(TypeError);
    });
  });

  describe('loadComponent', () => {
    it('returns a component from the Injector', () => {
      const MockComponent = () => null;
      window.Injector = {
        default: { component: { registerMany: vi.fn() } },
        loadComponent: vi.fn().mockReturnValue(MockComponent),
      };

      const result = loadComponent('TestComponent');

      expect(result).toBe(MockComponent);
      expect(window.Injector.loadComponent).toHaveBeenCalledWith('TestComponent', undefined);
    });

    it('passes context to Injector.loadComponent', () => {
      window.Injector = {
        default: { component: { registerMany: vi.fn() } },
        loadComponent: vi.fn().mockReturnValue(() => null),
      };

      const context = { area: 'test' };
      loadComponent('TestComponent', context);

      expect(window.Injector.loadComponent).toHaveBeenCalledWith('TestComponent', context);
    });

    it('throws TypeError when Injector global is missing', () => {
      // @ts-expect-error — simulating missing global
      delete window.Injector;

      expect(() => loadComponent('TestComponent')).toThrow(TypeError);
      expect(() => loadComponent('TestComponent')).toThrow('SilverStripe Injector is not available');
      expect(() => loadComponent('TestComponent')).toThrow('admin bundle is loaded');
    });

    it('throws TypeError when loadComponent is not a function', () => {
      // @ts-expect-error — simulating broken global
      window.Injector = { default: {}, loadComponent: 'not-a-function' };

      expect(() => loadComponent('TestComponent')).toThrow(TypeError);
    });
  });
});
