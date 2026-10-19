<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Notes and documents of a production for the house technicians.
 */
final class Version20261019120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Technician notes and files of productions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE production_file (id INT AUTO_INCREMENT NOT NULL, production_id INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, original_name VARCHAR(255) NOT NULL, stored_name VARCHAR(100) NOT NULL, mime_type VARCHAR(100) NOT NULL, size INT NOT NULL, UNIQUE INDEX UNIQ_578F7D041185AF6A (stored_name), INDEX IDX_578F7D04ECC6147F (production_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_file ADD CONSTRAINT FK_578F7D04ECC6147F FOREIGN KEY (production_id) REFERENCES production (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production ADD tech_notes_room LONGTEXT DEFAULT NULL, ADD tech_notes_light LONGTEXT DEFAULT NULL, ADD tech_notes_sound LONGTEXT DEFAULT NULL, ADD tech_notes_other LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production_file DROP FOREIGN KEY FK_578F7D04ECC6147F');
        $this->addSql('DROP TABLE production_file');
        $this->addSql('ALTER TABLE production DROP tech_notes_room, DROP tech_notes_light, DROP tech_notes_sound, DROP tech_notes_other');
    }
}
