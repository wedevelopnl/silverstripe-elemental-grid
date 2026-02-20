import type { ViewportConfig } from '@/types/adapter';

interface ViewportSwitcherProps {
  readonly viewports: readonly ViewportConfig[];
  readonly activeViewport: string;
  readonly onViewportChange: (key: string) => void;
}

export default function ViewportSwitcher({
  viewports,
  activeViewport,
  onViewportChange,
}: ViewportSwitcherProps) {
  return (
    <div className="viewport-switcher" role="group" aria-label="Viewport size">
      {viewports.map((viewport) => {
        const isActive = viewport.key === activeViewport;

        return (
          <button
            key={viewport.key}
            type="button"
            className={`viewport-switcher__button${isActive ? ' viewport-switcher__button--active' : ''}`}
            aria-pressed={isActive}
            aria-disabled={isActive || undefined}
            onClick={() => {
              if (!isActive) {
                onViewportChange(viewport.key);
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
