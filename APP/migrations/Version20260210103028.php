<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260210103028 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment ADD cleaning_contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84433F6D97C FOREIGN KEY (cleaning_contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_FE38F84433F6D97C ON appointment (cleaning_contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F84433F6D97C');
        $this->addSql('DROP INDEX IDX_FE38F84433F6D97C ON appointment');
        $this->addSql('ALTER TABLE appointment DROP cleaning_contact_id');
    }
}
