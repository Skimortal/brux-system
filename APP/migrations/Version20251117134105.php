<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251117134105 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_technician ADD production_id INT NOT NULL');
        $this->addSql('ALTER TABLE production_technician ADD CONSTRAINT FK_94E54DD3ECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
        $this->addSql('CREATE INDEX IDX_94E54DD3ECC6147F ON production_technician (production_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_technician DROP FOREIGN KEY FK_94E54DD3ECC6147F');
        $this->addSql('DROP INDEX IDX_94E54DD3ECC6147F ON production_technician');
        $this->addSql('ALTER TABLE production_technician DROP production_id');
    }
}
