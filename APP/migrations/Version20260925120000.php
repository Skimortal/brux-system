<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Data model cleanup for the redesign:
 * - contact categories get a functional role instead of hard-coded ids
 * - key lending moves into key_loan (planned handovers and history)
 * - appointments drop the legacy cleaning company link and can hold the staffing of a performance
 * - cleaning cancellations belong to a cleaning schedule; extra cleanings are appointments
 * - productions and performances track WordPress sync and push state
 */
final class Version20260925120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Redesign data model: contact roles, key loans, performance staffing, cleaning cancellations, sync state';
    }

    public function up(Schema $schema): void
    {
        // contact category roles, matched by name
        $this->addSql('ALTER TABLE contact_category ADD role VARCHAR(20) DEFAULT NULL');
        $this->addSql("UPDATE contact_category SET role = 'technician' WHERE role IS NULL AND LOWER(name) LIKE 'techn%' ORDER BY id LIMIT 1");
        $this->addSql("UPDATE contact_category SET role = 'cleaning' WHERE role IS NULL AND LOWER(name) LIKE 'reinig%' ORDER BY id LIMIT 1");
        $this->addSql("UPDATE contact_category SET role = 'volunteer' WHERE role IS NULL AND (LOWER(name) LIKE 'freiwillig%' OR LOWER(name) LIKE 'volunt%') ORDER BY id LIMIT 1");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C69855657698A6A ON contact_category (role)');

        // room order in the calendar
        $this->addSql('ALTER TABLE room ADD position INT DEFAULT 0 NOT NULL');
        $this->addSql('UPDATE room SET position = id');

        // key loans
        $this->addSql("CREATE TABLE key_loan (id INT AUTO_INCREMENT NOT NULL, key_id INT NOT NULL, contact_id INT DEFAULT NULL, user_id INT DEFAULT NULL, appointment_id INT DEFAULT NULL, production_event_id INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, holder_name VARCHAR(255) DEFAULT NULL, start_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', due_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', returned_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', note LONGTEXT DEFAULT NULL, INDEX IDX_43D4D6CAD145533 (key_id), INDEX IDX_43D4D6CAE7A1254A (contact_id), INDEX IDX_43D4D6CAA76ED395 (user_id), INDEX IDX_43D4D6CAE5B533F9 (appointment_id), INDEX IDX_43D4D6CA8980536F (production_event_id), INDEX IDX_43D4D6CAB75363F7 (start_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE key_loan ADD CONSTRAINT FK_43D4D6CAD145533 FOREIGN KEY (key_id) REFERENCES key_management (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE key_loan ADD CONSTRAINT FK_43D4D6CAE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE key_loan ADD CONSTRAINT FK_43D4D6CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE key_loan ADD CONSTRAINT FK_43D4D6CAE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE key_loan ADD CONSTRAINT FK_43D4D6CA8980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON DELETE SET NULL');

        // current and past lendings become loans; legacy holders without a contact/user keep their name
        $this->addSql(<<<'SQL'
            INSERT INTO key_loan (key_id, contact_id, user_id, holder_name, start_at, due_at, returned_at, note, created_at)
            SELECT k.id,
                   k.contact_id,
                   IF(k.contact_id IS NULL, k.user_id, NULL),
                   IF(k.contact_id IS NULL AND k.user_id IS NULL, COALESCE(t.name, c.name, p.title, 'Unbekannt'), NULL),
                   TIMESTAMP(COALESCE(k.borrow_date, CURRENT_DATE)),
                   IF(k.return_date IS NULL, NULL, TIMESTAMP(k.return_date, '23:59:00')),
                   IF(k.status = 'borrowed', NULL, TIMESTAMP(COALESCE(k.return_date, k.borrow_date), '23:59:00')),
                   'Aus der bisherigen Schlüsselverwaltung übernommen',
                   NOW()
            FROM key_management k
            LEFT JOIN technician t ON t.id = k.technician_id
            LEFT JOIN cleaning c ON c.id = k.cleaning_id
            LEFT JOIN production p ON p.id = k.production_id
            WHERE k.status = 'borrowed' OR k.borrow_date IS NOT NULL
            SQL);
        $this->addSql('ALTER TABLE key_management ADD lost TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE key_management SET lost = 1 WHERE status = 'lost'");
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0E6C5D496');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0ECC6147F');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0E7A1254A');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0A76ED395');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF08E5EB27B');
        $this->addSql('DROP INDEX IDX_BBE3CBF0E7A1254A ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF08E5EB27B ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF0ECC6147F ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF0A76ED395 ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF0E6C5D496 ON key_management');
        $this->addSql('ALTER TABLE key_management DROP user_id, DROP technician_id, DROP production_id, DROP cleaning_id, DROP contact_id, DROP status, DROP borrow_date, DROP return_date');

        // appointments: legacy cleaning company -> cleaning contact with the same name
        $this->addSql(<<<'SQL'
            UPDATE appointment a
            JOIN cleaning c ON c.id = a.cleaning_id
            JOIN contact ct ON ct.name = c.name
            SET a.cleaning_contact_id = ct.id
            WHERE a.cleaning_contact_id IS NULL
            SQL);
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8448E5EB27B');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844ECC6147F');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84454177093');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84433F6D97C');
        $this->addSql('DROP INDEX IDX_FE38F8448E5EB27B ON appointment');
        $this->addSql('ALTER TABLE appointment DROP color, DROP cleaning_id, ADD production_event_id INT DEFAULT NULL');

        // link production appointments of type "event" to the performance on the same day, if unambiguous
        $this->addSql(<<<'SQL'
            UPDATE appointment a
            JOIN (
                SELECT a2.id AS aid, MIN(pe.id) AS peid
                FROM appointment a2
                JOIN production_event pe ON pe.production_id = a2.production_id AND pe.date = DATE(a2.start_date)
                WHERE a2.type = 'production' AND a2.event_type = 'event'
                GROUP BY a2.id
                HAVING COUNT(*) = 1
            ) m ON m.aid = a.id
            SET a.production_event_id = m.peid
            SQL);
        $this->addSql(<<<'SQL'
            UPDATE appointment a
            JOIN (
                SELECT production_event_id AS pid, MIN(id) AS keep_id
                FROM appointment
                WHERE production_event_id IS NOT NULL
                GROUP BY production_event_id
                HAVING COUNT(*) > 1
            ) d ON d.pid = a.production_event_id AND a.id <> d.keep_id
            SET a.production_event_id = NULL
            SQL);
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8448980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844ECC6147F FOREIGN KEY (production_id) REFERENCES production (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84454177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84433F6D97C FOREIGN KEY (cleaning_contact_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_FE38F8448980536F ON appointment (production_event_id)');
        $this->addSql('CREATE INDEX IDX_FE38F84495275AB8 ON appointment (start_date)');
        $this->addSql('CREATE INDEX IDX_FE38F844845CBB3E ON appointment (end_date)');

        // cleaning cancellations belong to a schedule; extra cleanings are appointments already
        $this->addSql('ALTER TABLE cleaning_exception ADD schedule_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE cleaning_exception ce
            SET ce.schedule_id = (
                SELECT MIN(s.id) FROM cleaning_schedule s WHERE s.cleaning_contact_id = ce.cleaning_contact_id
            )
            WHERE ce.type = 'cancel'
            SQL);
        $this->addSql("DELETE FROM cleaning_exception WHERE type <> 'cancel' OR schedule_id IS NULL");
        $this->addSql(<<<'SQL'
            DELETE ce1 FROM cleaning_exception ce1
            JOIN cleaning_exception ce2 ON ce1.schedule_id = ce2.schedule_id AND ce1.date = ce2.date AND ce1.id > ce2.id
            SQL);
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0EE5B533F9');
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E33F6D97C');
        $this->addSql('DROP INDEX IDX_93055A0E33F6D97C ON cleaning_exception');
        $this->addSql('DROP INDEX IDX_93055A0EE5B533F9 ON cleaning_exception');
        $this->addSql("ALTER TABLE cleaning_exception CHANGE schedule_id schedule_id INT NOT NULL, DROP appointment_id, DROP cleaning_contact_id, DROP type, DROP time_from, DROP time_to, CHANGE date date DATE NOT NULL COMMENT '(DC2Type:date_immutable)'");
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0EA40BC2D5 FOREIGN KEY (schedule_id) REFERENCES cleaning_schedule (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_93055A0EA40BC2D5 ON cleaning_exception (schedule_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_cleaning_exception_schedule_date ON cleaning_exception (schedule_id, date)');

        // WordPress sync / push bookkeeping
        $this->addSql("ALTER TABLE production ADD synced_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_push_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_push_error LONGTEXT DEFAULT NULL");
        $this->addSql("ALTER TABLE production_event ADD synced_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_push_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', ADD last_push_error LONGTEXT DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Key lending, cleaning cancellations and appointment links were migrated; restore a backup instead.');
    }
}
