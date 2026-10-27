-- Hiraya MySQL least-privilege setup
-- ============================================================================
-- Local development uses SQLite, which has no database-user accounts. Run this
-- script only on the production/staging MySQL server, as a MySQL administrator
-- (root or equivalent), never as the application account.
--
-- Replace every REPLACE_WITH_* value first. Do not commit real passwords.
-- Change 'localhost' to the application server's restricted IP/host if the
-- database is on a separate machine.
--
-- WARNING: this resets the three Hiraya users. Run it during initial
-- provisioning or a controlled maintenance window.
-- ============================================================================

CREATE DATABASE IF NOT EXISTS hiraya
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

DROP USER IF EXISTS 'hiraya_app'@'localhost';
DROP USER IF EXISTS 'hiraya_migrate'@'localhost';
DROP USER IF EXISTS 'hiraya_backup'@'localhost';

-- Runtime account: normal application traffic only.
CREATE USER 'hiraya_app'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_APP_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE
  ON hiraya.*
  TO 'hiraya_app'@'localhost';

-- Migration account: use only while deploying schema changes.
CREATE USER 'hiraya_migrate'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_MIGRATE_PASSWORD';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON hiraya.*
  TO 'hiraya_migrate'@'localhost';

-- Backup account: read and lock only.
CREATE USER 'hiraya_backup'@'localhost'
  IDENTIFIED BY 'REPLACE_WITH_BACKUP_PASSWORD';

GRANT SELECT, LOCK TABLES, SHOW VIEW, EVENT, TRIGGER
  ON hiraya.*
  TO 'hiraya_backup'@'localhost';

FLUSH PRIVILEGES;

-- Deliberately not granted: FILE, SUPER, PROCESS, CREATE USER, GRANT OPTION,
-- or privileges on any database other than hiraya.
