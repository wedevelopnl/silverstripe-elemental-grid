interface CollapseToggleProps {
  readonly isCollapsed: boolean;
  readonly onToggle: () => void;
  readonly label: string;
}

export default function CollapseToggle({ isCollapsed, onToggle, label }: CollapseToggleProps) {
  return (
    <button
      type="button"
      className={`collapse-toggle${isCollapsed ? ' collapse-toggle--collapsed' : ''}`}
      aria-expanded={!isCollapsed}
      aria-label={isCollapsed ? `Expand ${label}` : `Collapse ${label}`}
      data-testid="collapse-toggle"
      onClick={(e) => { e.stopPropagation(); onToggle(); }}
    >
      <span className="collapse-toggle__chevron" aria-hidden="true" />
    </button>
  );
}
