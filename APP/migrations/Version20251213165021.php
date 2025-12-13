<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251213165021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF054177093');
        $this->addSql('DROP INDEX IDX_BBE3CBF054177093 ON key_management');
        $this->addSql('ALTER TABLE key_management ADD description LONGTEXT DEFAULT NULL, DROP room_id');
        $this->addSql('ALTER TABLE key_management_room RENAME INDEX idx_f15e8c3a31a580d TO IDX_6A570150C2880B1C');
        $this->addSql('ALTER TABLE key_management_room RENAME INDEX idx_f15e8c3541a5a TO IDX_6A57015054177093');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management ADD room_id INT DEFAULT NULL, DROP description');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF054177093 FOREIGN KEY (room_id) REFERENCES room (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_BBE3CBF054177093 ON key_management (room_id)');
        $this->addSql('ALTER TABLE key_management_room RENAME INDEX idx_6a570150c2880b1c TO IDX_F15E8C3A31A580D');
        $this->addSql('ALTER TABLE key_management_room RENAME INDEX idx_6a57015054177093 TO IDX_F15E8C3541A5A');
    }
}
