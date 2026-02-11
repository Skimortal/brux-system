<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211100230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management ADD contact_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF0E7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF0E7A1254A ON key_management (contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0E7A1254A');
        $this->addSql('DROP INDEX IDX_BBE3CBF0E7A1254A ON key_management');
        $this->addSql('ALTER TABLE key_management DROP contact_id');
    }
}
