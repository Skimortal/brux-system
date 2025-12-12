<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211101944 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE appointment_technician (id INT AUTO_INCREMENT NOT NULL, appointment_id INT NOT NULL, technician_id INT NOT NULL, confirmed TINYINT(1) NOT NULL, INDEX IDX_8B0DDB82E5B533F9 (appointment_id), INDEX IDX_8B0DDB82E6C5D496 (technician_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE appointment_volunteer (id INT AUTO_INCREMENT NOT NULL, appointment_id INT NOT NULL, volunteer_id INT NOT NULL, confirmed TINYINT(1) NOT NULL, tasks JSON DEFAULT NULL, INDEX IDX_C4CAB0D2E5B533F9 (appointment_id), INDEX IDX_C4CAB0D28EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B0DDB82E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment_technician ADD CONSTRAINT FK_8B0DDB82E6C5D496 FOREIGN KEY (technician_id) REFERENCES technician (id)');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D2E5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment_volunteer ADD CONSTRAINT FK_C4CAB0D28EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844E6C5D496');
        $this->addSql('DROP INDEX IDX_FE38F844E6C5D496 ON appointment');
        $this->addSql('ALTER TABLE appointment ADD type VARCHAR(255) NOT NULL, ADD event_type VARCHAR(255) DEFAULT NULL, ADD status VARCHAR(255) DEFAULT NULL, ADD internal_technicians_attending TINYINT(1) NOT NULL, ADD recurrence_frequency VARCHAR(50) DEFAULT NULL, ADD recurrence_end_date DATE DEFAULT NULL, CHANGE technician_id parent_appointment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844FB6847F2 FOREIGN KEY (parent_appointment_id) REFERENCES appointment (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_FE38F844FB6847F2 ON appointment (parent_appointment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E5B533F9');
        $this->addSql('ALTER TABLE appointment_technician DROP FOREIGN KEY FK_8B0DDB82E6C5D496');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D2E5B533F9');
        $this->addSql('ALTER TABLE appointment_volunteer DROP FOREIGN KEY FK_C4CAB0D28EFAB6B1');
        $this->addSql('DROP TABLE appointment_technician');
        $this->addSql('DROP TABLE appointment_volunteer');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844FB6847F2');
        $this->addSql('DROP INDEX IDX_FE38F844FB6847F2 ON appointment');
        $this->addSql('ALTER TABLE appointment DROP type, DROP event_type, DROP status, DROP internal_technicians_attending, DROP recurrence_frequency, DROP recurrence_end_date, CHANGE parent_appointment_id technician_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844E6C5D496 FOREIGN KEY (technician_id) REFERENCES technician (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_FE38F844E6C5D496 ON appointment (technician_id)');
    }
}
