<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125192327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment ADD cleaning_id INT DEFAULT NULL, ADD technician_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F8448E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id)');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844E6C5D496 FOREIGN KEY (technician_id) REFERENCES technician (id)');
        $this->addSql('CREATE INDEX IDX_FE38F8448E5EB27B ON appointment (cleaning_id)');
        $this->addSql('CREATE INDEX IDX_FE38F844E6C5D496 ON appointment (technician_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F8448E5EB27B');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844E6C5D496');
        $this->addSql('DROP INDEX IDX_FE38F8448E5EB27B ON appointment');
        $this->addSql('DROP INDEX IDX_FE38F844E6C5D496 ON appointment');
        $this->addSql('ALTER TABLE appointment DROP cleaning_id, DROP technician_id');
    }
}
