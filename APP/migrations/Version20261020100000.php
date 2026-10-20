<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Settings changed in the administration, and performances marked as premiere.
 */
final class Version20261020100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Settings and premiere flag';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE setting (name VARCHAR(100) NOT NULL, value LONGTEXT DEFAULT NULL, PRIMARY KEY(name)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_event ADD premiere TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE setting');
        $this->addSql('ALTER TABLE production_event DROP premiere');
    }
}
