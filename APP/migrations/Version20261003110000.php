<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Website categories of productions (VIP, Kinderprogramm, …) with a calendar colour.
 */
final class Version20261003110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Production categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE production_category (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, external_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) DEFAULT NULL, color VARCHAR(7) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_production_category (production_id INT NOT NULL, production_category_id INT NOT NULL, INDEX IDX_A78DA32DECC6147F (production_id), INDEX IDX_A78DA32D75FD768A (production_category_id), PRIMARY KEY(production_id, production_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_production_category ADD CONSTRAINT FK_A78DA32DECC6147F FOREIGN KEY (production_id) REFERENCES production (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_production_category ADD CONSTRAINT FK_A78DA32D75FD768A FOREIGN KEY (production_category_id) REFERENCES production_category (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE production_production_category');
        $this->addSql('DROP TABLE production_category');
    }
}
