<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Description text that some website posts put before the short description.
 */
final class Version20261003170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Intro section of the website post';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production ADD intro LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production DROP intro');
    }
}
