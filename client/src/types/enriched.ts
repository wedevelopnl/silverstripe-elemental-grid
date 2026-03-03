import type { SectionNode, RowNode, ColumnNode } from './elements';

export interface CollapseProps {
  readonly isCollapsed: boolean;
  readonly toggle: () => void;
}

export type EnrichedColumnNode = ColumnNode & CollapseProps;

export type EnrichedRowNode = Omit<RowNode, 'children'> & CollapseProps & {
  readonly children: EnrichedColumnNode[] | null;
};

export type EnrichedSectionNode = Omit<SectionNode, 'children'> & CollapseProps & {
  readonly children: EnrichedRowNode[] | null;
};
