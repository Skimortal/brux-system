<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * When a todo was ticked off, to list the done ones per group.
 */
final class Version20261022100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Todo done time';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE todo ADD done_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo DROP done_at');
    }
}
