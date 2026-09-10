-- Test migration: verify runner works (creates and drops a test table)
CREATE TABLE IF NOT EXISTS _migration_test (id INT PRIMARY KEY);
DROP TABLE IF EXISTS _migration_test;
