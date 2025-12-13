<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213150353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE production_event_contact_person (production_event_id INT NOT NULL, production_contact_person_id INT NOT NULL, INDEX IDX_75886EB78980536F (production_event_id), INDEX IDX_75886EB773C2F7FB (production_contact_person_id), PRIMARY KEY(production_event_id, production_contact_person_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_event_contact_person ADD CONSTRAINT FK_75886EB78980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_event_contact_person ADD CONSTRAINT FK_75886EB773C2F7FB FOREIGN KEY (production_contact_person_id) REFERENCES production_contact_person (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_event_contact_person DROP FOREIGN KEY FK_75886EB78980536F');
        $this->addSql('ALTER TABLE production_event_contact_person DROP FOREIGN KEY FK_75886EB773C2F7FB');
        $this->addSql('DROP TABLE production_event_contact_person');
    }
}
