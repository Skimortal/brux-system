<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223150248 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception ADD cleaning_contact_id INT DEFAULT NULL, CHANGE appointment_id appointment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0E33F6D97C FOREIGN KEY (cleaning_contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_93055A0E33F6D97C ON cleaning_exception (cleaning_contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E33F6D97C');
        $this->addSql('DROP INDEX IDX_93055A0E33F6D97C ON cleaning_exception');
        $this->addSql('ALTER TABLE cleaning_exception DROP cleaning_contact_id, CHANGE appointment_id appointment_id INT NOT NULL');
    }
}
