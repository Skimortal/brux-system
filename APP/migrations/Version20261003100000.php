<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sections of the website post (subtitle, general info, descriptions, press, credits,
 * additional info), edited separately and assembled into the post template.
 */
final class Version20261003100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Website post sections of productions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production ADD subtitle VARCHAR(255) DEFAULT NULL, ADD general_info LONGTEXT DEFAULT NULL, ADD short_description LONGTEXT DEFAULT NULL, ADD description LONGTEXT DEFAULT NULL, ADD press_quotes LONGTEXT DEFAULT NULL, ADD credits LONGTEXT DEFAULT NULL, ADD additional_info LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production DROP subtitle, DROP general_info, DROP short_description, DROP description, DROP press_quotes, DROP credits, DROP additional_info');
    }
}
