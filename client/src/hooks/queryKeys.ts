export const queryKeys = {
  elementTree: {
    all: () => ['elementTree'] as const,
    byPage: (pageId: number) => ['elementTree', pageId] as const,
  },
} as const;
