<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251124090702 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management ADD user_id INT DEFAULT NULL, ADD technician_id INT DEFAULT NULL, ADD production_id INT DEFAULT NULL, ADD cleaning_id INT DEFAULT NULL, DROP borrower_name, DROP signature');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF0E6C5D496 FOREIGN KEY (technician_id) REFERENCES technician (id)');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF0ECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
        $this->addSql('ALTER TABLE key_management ADD CONSTRAINT FK_BBE3CBF08E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF0A76ED395 ON key_management (user_id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF0E6C5D496 ON key_management (technician_id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF0ECC6147F ON key_management (production_id)');
        $this->addSql('CREATE INDEX IDX_BBE3CBF08E5EB27B ON key_management (cleaning_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0A76ED395');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0E6C5D496');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF0ECC6147F');
        $this->addSql('ALTER TABLE key_management DROP FOREIGN KEY FK_BBE3CBF08E5EB27B');
        $this->addSql('DROP INDEX IDX_BBE3CBF0A76ED395 ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF0E6C5D496 ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF0ECC6147F ON key_management');
        $this->addSql('DROP INDEX IDX_BBE3CBF08E5EB27B ON key_management');
        $this->addSql('ALTER TABLE key_management ADD borrower_name VARCHAR(255) DEFAULT NULL, ADD signature VARCHAR(255) DEFAULT NULL, DROP user_id, DROP technician_id, DROP production_id, DROP cleaning_id');
    }
}
