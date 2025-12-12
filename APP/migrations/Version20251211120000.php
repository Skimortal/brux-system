<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to add appointment type system and update existing data
 */
final class Version20251211120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add appointment type fields, create junction tables, and migrate existing appointment data';
    }

    public function up(Schema $schema): void
    {
        // Helper: Add column only if it doesn't exist
        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'type'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD type VARCHAR(50) DEFAULT NULL',
                'SELECT ''Column type already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'event_type'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD event_type VARCHAR(50) DEFAULT NULL',
                'SELECT ''Column event_type already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'status'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD status VARCHAR(50) DEFAULT NULL',
                'SELECT ''Column status already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'internal_technicians_attending'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD internal_technicians_attending TINYINT(1) DEFAULT 0 NOT NULL',
                'SELECT ''Column internal_technicians_attending already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'parent_appointment_id'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD parent_appointment_id INT DEFAULT NULL',
                'SELECT ''Column parent_appointment_id already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'recurrence_frequency'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD recurrence_frequency VARCHAR(50) DEFAULT NULL',
                'SELECT ''Column recurrence_frequency already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'recurrence_end_date'
            );
            SET @query = IF(@col_exists = 0,
                'ALTER TABLE appointment ADD recurrence_end_date DATE DEFAULT NULL',
                'SELECT ''Column recurrence_end_date already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // 2. Migrate existing data based on relations (only check existing columns)
        $this->addSql("UPDATE appointment SET type = 'cleaning' WHERE cleaning_id IS NOT NULL AND (type IS NULL OR type = '')");
        $this->addSql("UPDATE appointment SET type = 'production' WHERE production_id IS NOT NULL AND (type IS NULL OR type = '')");
        // REMOVED: technician_id check since column doesn't exist anymore
        $this->addSql("UPDATE appointment SET type = 'private' WHERE type IS NULL OR type = ''");

        // 3. Make type column NOT NULL
        $this->addSql("
            SET @col_exists = (
                SELECT COUNT(*)
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'type'
                AND IS_NULLABLE = 'YES'
            );
            SET @query = IF(@col_exists > 0,
                'ALTER TABLE appointment MODIFY type VARCHAR(50) NOT NULL',
                'SELECT ''Column type is already NOT NULL'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // 4. Add foreign key for parent_appointment_id if not exists
        $this->addSql("
            SET @fk_exists = (
                SELECT COUNT(*)
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND COLUMN_NAME = 'parent_appointment_id'
                AND REFERENCED_TABLE_NAME = 'appointment'
            );
            SET @query = IF(@fk_exists = 0,
                'ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844E455D23 FOREIGN KEY (parent_appointment_id) REFERENCES appointment (id) ON DELETE SET NULL',
                'SELECT ''FK for parent_appointment_id already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @idx_exists = (
                SELECT COUNT(*)
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment'
                AND INDEX_NAME = 'IDX_FE38F844E455D23'
            );
            SET @query = IF(@idx_exists = 0,
                'CREATE INDEX IDX_FE38F844E455D23 ON appointment (parent_appointment_id)',
                'SELECT ''Index on parent_appointment_id already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // 5. Create appointment_technician junction table if not exists
        $this->addSql("
            CREATE TABLE IF NOT EXISTS appointment_technician (
                id INT AUTO_INCREMENT NOT NULL,
                appointment_id INT NOT NULL,
                technician_id INT NOT NULL,
                confirmed TINYINT(1) DEFAULT 0 NOT NULL,
                INDEX IDX_8B2E1745E5B533F9 (appointment_id),
                INDEX IDX_8B2E1745E6315D38 (technician_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");

        // Add FKs only if they don't exist
        $this->addSql("
            SET @fk_exists = (
                SELECT COUNT(*)
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment_technician'
                AND CONSTRAINT_NAME = 'FK_8B2E1745E5B533F9'
            );
            SET @query = IF(@fk_exists = 0,
                'ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B2E1745E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE',
                'SELECT ''FK FK_8B2E1745E5B533F9 already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @fk_exists = (
                SELECT COUNT(*)
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment_technician'
                AND CONSTRAINT_NAME = 'FK_8B2E1745E6315D38'
            );
            SET @query = IF(@fk_exists = 0,
                'ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B2E1745E6315D38 FOREIGN KEY (technician_id) REFERENCES technician (id) ON DELETE CASCADE',
                'SELECT ''FK FK_8B2E1745E6315D38 already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // 6. Create appointment_volunteer junction table if not exists
        $this->addSql("
            CREATE TABLE IF NOT EXISTS appointment_volunteer (
                id INT AUTO_INCREMENT NOT NULL,
                appointment_id INT NOT NULL,
                volunteer_id INT NOT NULL,
                confirmed TINYINT(1) DEFAULT 0 NOT NULL,
                tasks JSON DEFAULT NULL,
                INDEX IDX_9F4B9E79E5B533F9 (appointment_id),
                INDEX IDX_9F4B9E798EFAB6B1 (volunteer_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ");

        $this->addSql("
            SET @fk_exists = (
                SELECT COUNT(*)
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment_volunteer'
                AND CONSTRAINT_NAME = 'FK_9F4B9E79E5B533F9'
            );
            SET @query = IF(@fk_exists = 0,
                'ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_9F4B9E79E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE',
                'SELECT ''FK FK_9F4B9E79E5B533F9 already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @fk_exists = (
                SELECT COUNT(*)
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'appointment_volunteer'
                AND CONSTRAINT_NAME = 'FK_9F4B9E798EFAB6B1'
            );
            SET @query = IF(@fk_exists = 0,
                'ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_9F4B9E798EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE',
                'SELECT ''FK FK_9F4B9E798EFAB6B1 already exists'' AS message'
            );
            PREPARE stmt FROM @query;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop junction tables
        $this->addSql('DROP TABLE IF EXISTS appointment_technician');
        $this->addSql('DROP TABLE IF EXISTS appointment_volunteer');

        // Drop parent appointment FK and index
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY IF EXISTS FK_FE38F844E455D23');
        $this->addSql('DROP INDEX IF EXISTS IDX_FE38F844E455D23 ON appointment');

        // Remove new columns
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS recurrence_end_date');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS recurrence_frequency');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS parent_appointment_id');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS internal_technicians_attending');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS status');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS event_type');
        $this->addSql('ALTER TABLE appointment DROP COLUMN IF EXISTS type');
    }
}
