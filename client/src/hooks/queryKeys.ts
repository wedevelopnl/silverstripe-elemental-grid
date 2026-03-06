export const queryKeys = {
  elementTree: {
    all: () => ['elementTree'] as const,
    byPage: (pageId: number, zone: string) => ['elementTree', pageId, zone] as const,
  },
} as const;
