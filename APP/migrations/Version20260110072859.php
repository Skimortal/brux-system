<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110072859 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_technician ADD lighting TINYINT(1) DEFAULT 0 NOT NULL, ADD sound TINYINT(1) DEFAULT 0 NOT NULL, ADD setup TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE production ADD needs_lighting_technician TINYINT(1) DEFAULT 0 NOT NULL, ADD needs_sound_technician TINYINT(1) DEFAULT 0 NOT NULL, ADD needs_setup_technician TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment_technician DROP lighting, DROP sound, DROP setup');
        $this->addSql('ALTER TABLE production DROP needs_lighting_technician, DROP needs_sound_technician, DROP needs_setup_technician');
    }
}
