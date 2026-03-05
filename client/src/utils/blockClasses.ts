import type { ElementStatus } from '@/types/status';

export function buildBlockClasses(
  block: string,
  status: ElementStatus,
  modifiers: Record<string, boolean>,
): string {
  const classes = [block, `${block}--${status}`];

  for (const [modifier, active] of Object.entries(modifiers)) {
    if (active) {
      classes.push(`${block}--${modifier}`);
    }
  }

  return classes.join(' ');
}
