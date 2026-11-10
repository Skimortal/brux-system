<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * News for technicians and volunteers held back for the daily digest mail.
 */
final class Version20261110100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Digest items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE digest_item (id INT AUTO_INCREMENT NOT NULL, contact_id INT NOT NULL, appointment_id INT NOT NULL, kind VARCHAR(20) NOT NULL, more INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_CF5E1E27E7A1254A (contact_id), INDEX IDX_CF5E1E27E5B533F9 (appointment_id), INDEX IDX_CF5E1E27E7A1254A8B8E8428 (contact_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE digest_item ADD CONSTRAINT FK_CF5E1E27E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE digest_item ADD CONSTRAINT FK_CF5E1E27E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE digest_item DROP FOREIGN KEY FK_CF5E1E27E7A1254A');
        $this->addSql('ALTER TABLE digest_item DROP FOREIGN KEY FK_CF5E1E27E5B533F9');
        $this->addSql('DROP TABLE digest_item');
    }
}
