<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Productions carry their website publication state; the ones imported from
 * WordPress are live there already.
 */
final class Version20261002080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Website publication state of productions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE production ADD website_status VARCHAR(20) DEFAULT 'draft' NOT NULL");
        $this->addSql("UPDATE production SET website_status = 'publish' WHERE external_id IS NOT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production DROP website_status');
    }
}
