/** @type {import('@stryker-mutator/api/core').PartialStrykerOptions} */
export default {
  testRunner: 'vitest',
  mutate: [
    'client/src/**/*.{ts,tsx}',
    '!client/src/**/*.{test,spec}.*',
    '!client/src/**/*.d.ts',
    '!client/src/**/tests/**',
  ],
  ignorePatterns: ['public'],
  checkers: ['typescript'],
  tsconfigFile: 'tsconfig.json',
  reporters: ['progress', 'clear-text', 'json'],
  jsonReporter: {
    fileName: 'reports/mutation/mutation.json',
  },
  thresholds: {
    high: 80,
    low: 60,
    break: 70,
  },
  // Allow clean exit when no source files exist to mutate yet
  allowEmpty: true,
};
