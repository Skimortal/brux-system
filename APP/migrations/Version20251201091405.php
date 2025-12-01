<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201091405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning DROP cleaning_date, DROP general_areas, DROP black_room, DROP white_room, DROP backstage_toilets, DROP dressing_room, DROP backstage_corridor, DROP office_ground_floor');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning ADD cleaning_date DATETIME DEFAULT NULL, ADD general_areas TINYINT(1) DEFAULT NULL, ADD black_room TINYINT(1) DEFAULT NULL, ADD white_room TINYINT(1) DEFAULT NULL, ADD backstage_toilets TINYINT(1) DEFAULT NULL, ADD dressing_room TINYINT(1) DEFAULT NULL, ADD backstage_corridor TINYINT(1) DEFAULT NULL, ADD office_ground_floor TINYINT(1) DEFAULT NULL');
    }
}
