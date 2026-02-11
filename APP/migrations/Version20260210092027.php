<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210092027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B2E1745E5B533F9');
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E6C5D496');
        $this->addSql('DROP INDEX IDX_8B0DDB82E6C5D496 ON appointment_technician');
        $this->addSql('ALTER TABLE appointment_technician CHANGE lighting lighting TINYINT(1) NOT NULL, CHANGE sound sound TINYINT(1) NOT NULL, CHANGE setup setup TINYINT(1) NOT NULL, CHANGE technician_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B0DDB82E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B0DDB82E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_8B0DDB82E7A1254A ON appointment_technician (contact_id)');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D28EFAB6B1');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D2E5B533F9');
        $this->addSql('DROP INDEX IDX_C4CAB0D28EFAB6B1 ON appointment_volunteer');
        $this->addSql('ALTER TABLE appointment_volunteer CHANGE volunteer_id contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D2E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D2E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('CREATE INDEX IDX_C4CAB0D2E7A1254A ON appointment_volunteer (contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E5B533F9');
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E7A1254A');
        $this->addSql('DROP INDEX IDX_8B0DDB82E7A1254A ON appointment_technician');
        $this->addSql('ALTER TABLE appointment_technician CHANGE lighting lighting TINYINT(1) DEFAULT 0 NOT NULL, CHANGE sound sound TINYINT(1) DEFAULT 0 NOT NULL, CHANGE setup setup TINYINT(1) DEFAULT 0 NOT NULL, CHANGE contact_id technician_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B2E1745E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B0DDB82E6C5D496 FOREIGN KEY (technician_id) REFERENCES technician (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_8B0DDB82E6C5D496 ON appointment_technician (technician_id)');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D2E7A1254A');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D2E5B533F9');
        $this->addSql('DROP INDEX IDX_C4CAB0D2E7A1254A ON appointment_volunteer');
        $this->addSql('ALTER TABLE appointment_volunteer CHANGE contact_id volunteer_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D28EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D2E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_C4CAB0D28EFAB6B1 ON appointment_volunteer (volunteer_id)');
    }
}
