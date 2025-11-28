<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128131551 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_category (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, external_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE event_price (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, price_index INT DEFAULT NULL, price_label VARCHAR(100) DEFAULT NULL, category_label VARCHAR(255) DEFAULT NULL, reserved_seats INT DEFAULT NULL, incoming_reservations INT DEFAULT NULL, INDEX IDX_8BD393F571F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_event_category (production_event_id INT NOT NULL, event_category_id INT NOT NULL, INDEX IDX_E75D2ACD8980536F (production_event_id), INDEX IDX_E75D2ACDB9CF4E62 (event_category_id), PRIMARY KEY(production_event_id, event_category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_price (id INT AUTO_INCREMENT NOT NULL, production_id INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, price_index INT DEFAULT NULL, price_label VARCHAR(100) DEFAULT NULL, category_label VARCHAR(255) DEFAULT NULL, parent_reserved INT DEFAULT NULL, INDEX IDX_D0C9E6EFECC6147F (production_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_price ADD CONSTRAINT FK_8BD393F571F7E88B FOREIGN KEY (event_id) REFERENCES production_event (id)');
        $this->addSql('ALTER TABLE production_event_category ADD CONSTRAINT FK_E75D2ACD8980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_event_category ADD CONSTRAINT FK_E75D2ACDB9CF4E62 FOREIGN KEY (event_category_id) REFERENCES event_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_price ADD CONSTRAINT FK_D0C9E6EFECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
        $this->addSql('ALTER TABLE production DROP prices');
        $this->addSql('ALTER TABLE production_event ADD room_id INT DEFAULT NULL, DROP room, DROP categories, DROP prices');
        $this->addSql('ALTER TABLE production_event ADD CONSTRAINT FK_21AFCE9154177093 FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_21AFCE9154177093 ON production_event (room_id)');
        $this->addSql('ALTER TABLE room ADD external_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_729F519B9F75D7B0 ON room (external_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_price DROP FOREIGN KEY FK_8BD393F571F7E88B');
        $this->addSql('ALTER TABLE production_event_category DROP FOREIGN KEY FK_E75D2ACD8980536F');
        $this->addSql('ALTER TABLE production_event_category DROP FOREIGN KEY FK_E75D2ACDB9CF4E62');
        $this->addSql('ALTER TABLE production_price DROP FOREIGN KEY FK_D0C9E6EFECC6147F');
        $this->addSql('DROP TABLE event_category');
        $this->addSql('DROP TABLE event_price');
        $this->addSql('DROP TABLE production_event_category');
        $this->addSql('DROP TABLE production_price');
        $this->addSql('ALTER TABLE production ADD prices JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE production_event DROP FOREIGN KEY FK_21AFCE9154177093');
        $this->addSql('DROP INDEX IDX_21AFCE9154177093 ON production_event');
        $this->addSql('ALTER TABLE production_event ADD room VARCHAR(100) DEFAULT NULL, ADD categories JSON DEFAULT NULL, ADD prices JSON DEFAULT NULL, DROP room_id');
        $this->addSql('DROP INDEX UNIQ_729F519B9F75D7B0 ON room');
        $this->addSql('ALTER TABLE room DROP external_id');
    }
}
