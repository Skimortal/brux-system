<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Calendar subscriptions (iCal) and appointments released for the board.
 */
final class Version20261019140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Calendar feeds';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE calendar_feed (id INT AUTO_INCREMENT NOT NULL, contact_id INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(120) NOT NULL, profile VARCHAR(20) NOT NULL, token VARCHAR(64) NOT NULL, UNIQUE INDEX UNIQ_71D6C7FF5F37A13B (token), INDEX IDX_71D6C7FFE7A1254A (contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE calendar_feed ADD CONSTRAINT FK_71D6C7FFE7A1254A FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE appointment ADD board_visible TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE calendar_feed DROP FOREIGN KEY FK_71D6C7FFE7A1254A');
        $this->addSql('DROP TABLE calendar_feed');
        $this->addSql('ALTER TABLE appointment DROP board_visible');
    }
}
