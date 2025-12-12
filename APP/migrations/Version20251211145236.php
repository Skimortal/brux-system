<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211145236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_FE38F844E455D23 ON appointment');
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B2E1745E6315D38');
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E5B533F9');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_9F4B9E79E5B533F9');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_9F4B9E798EFAB6B1');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_FE38F844E455D23 ON appointment (parent_appointment_id)');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B2E1745E6315D38 FOREIGN KEY (technician_id) REFERENCES technician (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_9F4B9E798EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
