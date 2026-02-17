-- Grant CREATE/DROP DATABASE privileges for SilverStripe's test framework
-- which creates temporary databases matching ss_tmpdb_*
GRANT ALL PRIVILEGES ON *.* TO 'silverstripe'@'%';
FLUSH PRIVILEGES;
