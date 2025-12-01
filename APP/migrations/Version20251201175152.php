<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201175152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE production_contact_person (id INT AUTO_INCREMENT NOT NULL, production_id INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, nachname VARCHAR(255) NOT NULL, hauptansprechperson TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_3B33C2BECC6147F (production_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_contact_person ADD CONSTRAINT FK_3B33C2BECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_contact_person DROP FOREIGN KEY FK_3B33C2BECC6147F');
        $this->addSql('DROP TABLE production_contact_person');
    }
}
