<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128093940 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_event_production_technician DROP FOREIGN KEY FK_DAB06D987C396972');
        $this->addSql('ALTER TABLE production_event_production_technician DROP FOREIGN KEY FK_DAB06D988980536F');
        $this->addSql('DROP TABLE production_event_production_technician');
        $this->addSql('ALTER TABLE production ADD external_id INT DEFAULT NULL, ADD title VARCHAR(255) NOT NULL, ADD permalink VARCHAR(500) DEFAULT NULL, ADD post_thumbnail_url VARCHAR(500) DEFAULT NULL, ADD content_html LONGTEXT DEFAULT NULL, ADD excerpt_html LONGTEXT DEFAULT NULL, DROP type, DROP group_name, DROP main_contact_name, DROP address, DROP phone, DROP email, DROP main_contact_function, DROP person_name, DROP person_address, DROP person_phone, DROP person_email, CHANGE group_members prices JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE production_event ADD event_index INT DEFAULT NULL, ADD date DATE DEFAULT NULL, ADD time_from VARCHAR(10) DEFAULT NULL, ADD time_to VARCHAR(10) DEFAULT NULL, ADD room VARCHAR(100) DEFAULT NULL, ADD status VARCHAR(50) DEFAULT NULL, ADD reservation_status VARCHAR(50) DEFAULT NULL, ADD quota INT DEFAULT NULL, ADD incoming_total INT DEFAULT NULL, ADD free_seats INT DEFAULT NULL, ADD reservation_note LONGTEXT DEFAULT NULL, ADD categories JSON DEFAULT NULL, ADD prices JSON DEFAULT NULL, ADD reservations JSON DEFAULT NULL, DROP name, DROP presence_start_date, DROP presence_end_date, DROP performance_dates, DROP rehearsal_dates, DROP setup_dates, DROP teardown_dates, DROP general_rehearsal_date, DROP photo_session_date, DROP key_handover_date, DROP key_return_date, DROP main_rehearsals, DROP photos, DROP trailer, DROP project_description, DROP info_texts, DROP desired_ticket_prices, DROP duration, DROP credits_and_bios, DROP technical_rider, CHANGE production_id production_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE production_event_production_technician (production_event_id INT NOT NULL, production_technician_id INT NOT NULL, INDEX IDX_DAB06D988980536F (production_event_id), INDEX IDX_DAB06D987C396972 (production_technician_id), PRIMARY KEY(production_event_id, production_technician_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE production_event_production_technician ADD CONSTRAINT FK_DAB06D987C396972 FOREIGN KEY (production_technician_id) REFERENCES production_technician (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_event_production_technician ADD CONSTRAINT FK_DAB06D988980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production ADD type VARCHAR(50) NOT NULL, ADD group_name VARCHAR(255) DEFAULT NULL, ADD main_contact_name VARCHAR(255) DEFAULT NULL, ADD address LONGTEXT DEFAULT NULL, ADD phone VARCHAR(255) DEFAULT NULL, ADD email VARCHAR(255) DEFAULT NULL, ADD main_contact_function VARCHAR(255) DEFAULT NULL, ADD person_name VARCHAR(255) DEFAULT NULL, ADD person_address LONGTEXT DEFAULT NULL, ADD person_phone VARCHAR(255) DEFAULT NULL, ADD person_email VARCHAR(255) DEFAULT NULL, DROP external_id, DROP title, DROP permalink, DROP post_thumbnail_url, DROP content_html, DROP excerpt_html, CHANGE prices group_members JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE production_event ADD name VARCHAR(255) NOT NULL, ADD presence_end_date DATE DEFAULT NULL, ADD performance_dates JSON DEFAULT NULL, ADD rehearsal_dates JSON DEFAULT NULL, ADD setup_dates JSON DEFAULT NULL, ADD teardown_dates JSON DEFAULT NULL, ADD general_rehearsal_date DATETIME DEFAULT NULL, ADD photo_session_date DATETIME DEFAULT NULL, ADD key_handover_date DATETIME DEFAULT NULL, ADD key_return_date DATETIME DEFAULT NULL, ADD main_rehearsals JSON DEFAULT NULL, ADD photos JSON DEFAULT NULL, ADD project_description LONGTEXT DEFAULT NULL, ADD info_texts LONGTEXT DEFAULT NULL, ADD desired_ticket_prices LONGTEXT DEFAULT NULL, ADD duration VARCHAR(255) DEFAULT NULL, ADD credits_and_bios LONGTEXT DEFAULT NULL, ADD technical_rider VARCHAR(255) DEFAULT NULL, DROP event_index, DROP time_from, DROP time_to, DROP room, DROP status, DROP reservation_status, DROP quota, DROP incoming_total, DROP free_seats, DROP categories, DROP prices, DROP reservations, CHANGE production_id production_id INT DEFAULT NULL, CHANGE date presence_start_date DATE DEFAULT NULL, CHANGE reservation_note trailer LONGTEXT DEFAULT NULL');
    }
}
