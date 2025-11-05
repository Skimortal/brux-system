<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105142246 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cleaning (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT DEFAULT NULL, cleaning_date DATETIME DEFAULT NULL, cleaning_type VARCHAR(255) DEFAULT NULL, general_areas TINYINT(1) DEFAULT NULL, black_room TINYINT(1) DEFAULT NULL, white_room TINYINT(1) DEFAULT NULL, backstage_toilets TINYINT(1) DEFAULT NULL, dressing_room TINYINT(1) DEFAULT NULL, backstage_corridor TINYINT(1) DEFAULT NULL, office_ground_floor TINYINT(1) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE key_management (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, key_color VARCHAR(255) NOT NULL, status VARCHAR(50) NOT NULL, borrower_name VARCHAR(255) DEFAULT NULL, borrow_date DATE DEFAULT NULL, return_date DATE DEFAULT NULL, signature VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, type VARCHAR(50) NOT NULL, group_name VARCHAR(255) DEFAULT NULL, main_contact_name VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, main_contact_function VARCHAR(255) DEFAULT NULL, group_members JSON DEFAULT NULL, person_name VARCHAR(255) DEFAULT NULL, person_address LONGTEXT DEFAULT NULL, person_phone VARCHAR(255) DEFAULT NULL, person_email VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_event (id INT AUTO_INCREMENT NOT NULL, production_id INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, presence_start_date DATE DEFAULT NULL, presence_end_date DATE DEFAULT NULL, performance_dates JSON DEFAULT NULL, rehearsal_dates JSON DEFAULT NULL, setup_dates JSON DEFAULT NULL, teardown_dates JSON DEFAULT NULL, general_rehearsal_date DATETIME DEFAULT NULL, photo_session_date DATETIME DEFAULT NULL, key_handover_date DATETIME DEFAULT NULL, key_return_date DATETIME DEFAULT NULL, main_rehearsals JSON DEFAULT NULL, photos JSON DEFAULT NULL, trailer LONGTEXT DEFAULT NULL, project_description LONGTEXT DEFAULT NULL, info_texts LONGTEXT DEFAULT NULL, desired_ticket_prices LONGTEXT DEFAULT NULL, duration VARCHAR(255) DEFAULT NULL, credits_and_bios LONGTEXT DEFAULT NULL, technical_rider VARCHAR(255) DEFAULT NULL, INDEX IDX_21AFCE91ECC6147F (production_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_event_production_technician (production_event_id INT NOT NULL, production_technician_id INT NOT NULL, INDEX IDX_DAB06D988980536F (production_event_id), INDEX IDX_DAB06D987C396972 (production_technician_id), PRIMARY KEY(production_event_id, production_technician_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE production_technician (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE room (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE technician (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteer (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, address LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteer_payment (id INT AUTO_INCREMENT NOT NULL, volunteer_id INT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, amount NUMERIC(10, 2) NOT NULL, payment_date DATE NOT NULL, proof_document VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, INDEX IDX_B3C09B358EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE production_event ADD CONSTRAINT FK_21AFCE91ECC6147F FOREIGN KEY (production_id) REFERENCES production (id)');
        $this->addSql('ALTER TABLE production_event_production_technician ADD CONSTRAINT FK_DAB06D988980536F FOREIGN KEY (production_event_id) REFERENCES production_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE production_event_production_technician ADD CONSTRAINT FK_DAB06D987C396972 FOREIGN KEY (production_technician_id) REFERENCES production_technician (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_payment ADD CONSTRAINT FK_B3C09B358EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE production_event DROP FOREIGN KEY FK_21AFCE91ECC6147F');
        $this->addSql('ALTER TABLE production_event_production_technician DROP FOREIGN KEY FK_DAB06D988980536F');
        $this->addSql('ALTER TABLE production_event_production_technician DROP FOREIGN KEY FK_DAB06D987C396972');
        $this->addSql('ALTER TABLE volunteer_payment DROP FOREIGN KEY FK_B3C09B358EFAB6B1');
        $this->addSql('DROP TABLE cleaning');
        $this->addSql('DROP TABLE key_management');
        $this->addSql('DROP TABLE production');
        $this->addSql('DROP TABLE production_event');
        $this->addSql('DROP TABLE production_event_production_technician');
        $this->addSql('DROP TABLE production_technician');
        $this->addSql('DROP TABLE room');
        $this->addSql('DROP TABLE technician');
        $this->addSql('DROP TABLE volunteer');
        $this->addSql('DROP TABLE volunteer_payment');
    }
}
