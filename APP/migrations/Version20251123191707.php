<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123191707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management ADD room_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF054177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF054177093 ON key_management (room_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF054177093');
        $this->addSql('DROP INDEX IDX_BBE3CBF054177093 ON key_management');
        $this->addSql('ALTER TABLE key_management DROP room_id');
    }
}
