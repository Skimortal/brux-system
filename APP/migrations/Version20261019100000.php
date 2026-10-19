<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Groups for todos, e.g. a shopping list; existing todos stay without a group.
 */
final class Version20261019100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Todo groups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE todo_group (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE todo ADD todo_group_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE todo ADD CONSTRAINT FK_5A0EB6A0D9839127 FOREIGN KEY (todo_group_id) REFERENCES todo_group (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_5A0EB6A0D9839127 ON todo (todo_group_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE todo DROP FOREIGN KEY FK_5A0EB6A0D9839127');
        $this->addSql('DROP INDEX IDX_5A0EB6A0D9839127 ON todo');
        $this->addSql('ALTER TABLE todo DROP todo_group_id');
        $this->addSql('DROP TABLE todo_group');
    }
}
