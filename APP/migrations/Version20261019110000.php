<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Productions can be pencilled in ("Vielleicht"); they stay off the website until confirmed.
 */
final class Version20261019110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Tentative productions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production ADD tentative TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production DROP tentative');
    }
}
