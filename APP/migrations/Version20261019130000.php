<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Technician assignments get a status (assigned, accepted, declined, confirmed, interested)
 * instead of a confirmed flag; user accounts can belong to a technician contact; appointments
 * can be sent out to all technicians as open.
 */
final class Version20261019130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Technician assignment status and technician accounts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE appointment_technician ADD status VARCHAR(20) DEFAULT 'assigned' NOT NULL, ADD responded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        $this->addSql("UPDATE appointment_technician SET status = 'confirmed' WHERE confirmed = 1");
        $this->addSql('ALTER TABLE appointment_technician DROP confirmed');
        $this->addSql('ALTER TABLE user ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7A1254A ON user (contact_id)');
        $this->addSql("ALTER TABLE appointment ADD open_call_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment_technician ADD confirmed TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE appointment_technician SET confirmed = 1 WHERE status = 'confirmed'");
        $this->addSql('ALTER TABLE appointment_technician DROP status, DROP responded_at');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649E7A1254A');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7A1254A ON user');
        $this->addSql('ALTER TABLE user DROP contact_id');
        $this->addSql('ALTER TABLE appointment DROP open_call_at');
    }
}
