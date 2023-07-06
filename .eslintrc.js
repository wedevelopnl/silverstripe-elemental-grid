const config = require('@silverstripe/eslint-config/.eslintrc');

config.rules['import/no-unresolved'] = 'off';
config.rules['import/extensions'] = 'off';
config.rules['react/jsx-boolean-value'] = 'always';

module.exports = config;
