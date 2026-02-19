import './EmptyState.scss';

interface EmptyStateProps {
  readonly message: string;
  readonly variant?: 'centered';
}

export default function EmptyState({ message, variant }: EmptyStateProps) {
  const className = variant === 'centered'
    ? 'empty-state empty-state--centered'
    : 'empty-state';

  return (
    <div className={className}>
      {message}
    </div>
  );
}
