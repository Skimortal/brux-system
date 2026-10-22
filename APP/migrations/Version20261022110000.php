<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * A contact category for the board, so its members can be invited to their read-only area.
 */
final class Version20261022110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Board contact category';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO contact_category (name, role) SELECT 'Vorstand', 'board' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM contact_category WHERE role = 'board')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM contact_category WHERE role = 'board' AND name = 'Vorstand' AND NOT EXISTS (SELECT 1 FROM contact WHERE contact.category_id = contact_category.id)");
    }
}
