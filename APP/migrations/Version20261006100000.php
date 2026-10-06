<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The period a production's group is in the house, optionally in one room.
 */
final class Version20261006100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stay period of productions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE production ADD stay_from DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', ADD stay_until DATE DEFAULT NULL COMMENT '(DC2Type:date_immutable)', ADD stay_room_id INT DEFAULT NULL");
        $this->addSql('ALTER TABLE production ADD CONSTRAINT FK_D3EDB1E041A2E05D FOREIGN KEY (stay_room_id) REFERENCES room (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_D3EDB1E041A2E05D ON production (stay_room_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE production DROP FOREIGN KEY FK_D3EDB1E041A2E05D');
        $this->addSql('DROP INDEX IDX_D3EDB1E041A2E05D ON production');
        $this->addSql('ALTER TABLE production DROP stay_from, DROP stay_until, DROP stay_room_id');
    }
}
