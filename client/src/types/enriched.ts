import type { SectionNode, RowNode, ColumnNode, SimpleElementNode } from './elements';

export interface CollapseProps {
  readonly isCollapsed: boolean;
  readonly toggle: () => void;
}

export interface SortableProps {
  readonly sortableId: string;
}

export type EnrichedSimpleElementNode = SimpleElementNode & SortableProps;

export type EnrichedColumnNode = Omit<ColumnNode, 'children'> & CollapseProps & SortableProps & {
  readonly children: EnrichedSimpleElementNode[] | null;
  readonly childSortableIds: string[];
};

export type EnrichedRowNode = Omit<RowNode, 'children'> & CollapseProps & SortableProps & {
  readonly children: EnrichedColumnNode[] | null;
  readonly childSortableIds: string[];
};

export type EnrichedSectionNode = Omit<SectionNode, 'children'> & CollapseProps & SortableProps & {
  readonly children: EnrichedRowNode[] | null;
  readonly childSortableIds: string[];
};
