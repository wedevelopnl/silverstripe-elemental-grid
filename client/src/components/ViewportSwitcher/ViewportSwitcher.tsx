import { useViewportContext } from '@/hooks/ViewportContext';
import { getViewports } from '@/utils/gridAdapter';

export default function ViewportSwitcher() {
  const viewports = getViewports();
  const { activeViewport, setActiveViewport } = useViewportContext();

  return (
    <div className="viewport-switcher" role="group" aria-label="Viewport size">
      {viewports.map((viewport) => {
        const isActive = viewport.key === activeViewport;

        return (
          <button
            key={viewport.key}
            type="button"
            className={`viewport-switcher__button${isActive ? ' viewport-switcher__button--active' : ''}`}
            data-testid="viewport-button"
            aria-pressed={isActive}
            aria-disabled={isActive || undefined}
            onClick={() => {
              if (!isActive) {
                setActiveViewport(viewport.key);
              }
            }}
          >
            {viewport.label}
          </button>
        );
      })}
    </div>
  );
}
