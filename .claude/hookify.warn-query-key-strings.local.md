---
name: warn-query-key-strings
enabled: true
event: file
action: warn
conditions:
  - field: new_text
    operator: regex_match
    pattern: (invalidateQueries|setQueryData|getQueryData|removeQueries|cancelQueries)\(\s*(\{|\[)\s*['"]
  - field: file_path
    operator: regex_match
    pattern: client/src/.*\.(ts|tsx)$
---

**Use Query Key Factory — No String Literals in Cache Operations**

Do NOT use inline string literals for TanStack Query cache keys. A typo in a string key silently breaks cache invalidation, causing stale UI with no error.

**Use the `queryKeys` factory instead:**

```typescript
import { queryKeys } from '@/hooks/queryKeys';

// Correct
queryClient.invalidateQueries({ queryKey: queryKeys.elementTree(pageId) });

// Wrong — typo risk, no type safety
queryClient.invalidateQueries({ queryKey: ['elementTree', pageId] });
```

See `client/src/hooks/queryKeys.ts` for all available key factories.
