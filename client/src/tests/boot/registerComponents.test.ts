import { registerComponents } from '@/boot/registerComponents';
import GridEditor from '@/components/GridEditor/GridEditor';

describe('registerComponents', () => {
  const originalInjector = window.Injector;

  afterEach(() => {
    window.Injector = originalInjector;
  });

  it('registers GridEditor with the Injector', () => {
    const registerMany = vi.fn();
    window.Injector = {
      default: { component: { registerMany } },
      loadComponent: vi.fn(),
    };

    registerComponents();

    expect(registerMany).toHaveBeenCalledOnce();
    expect(registerMany).toHaveBeenCalledWith({ GridEditor });
  });
});
