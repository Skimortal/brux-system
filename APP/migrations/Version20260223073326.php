<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223073326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cleaning_exception (id INT AUTO_INCREMENT NOT NULL, cleaning_id INT NOT NULL, date DATE NOT NULL, type VARCHAR(20) NOT NULL, time_from TIME DEFAULT NULL, time_to TIME DEFAULT NULL, INDEX IDX_93055A0E8E5EB27B (cleaning_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cleaning_schedule (id INT AUTO_INCREMENT NOT NULL, cleaning_id INT NOT NULL, weekdays JSON NOT NULL, time_from TIME NOT NULL, time_to TIME NOT NULL, active_from DATE DEFAULT NULL, active_to DATE DEFAULT NULL, INDEX IDX_F51E543A8E5EB27B (cleaning_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0E8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id)');
        $this->addSql('ALTER TABLE cleaning_schedule ADD CONSTRAINT FK_F51E543A8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E8E5EB27B');
        $this->addSql('ALTER TABLE cleaning_schedule DROP FOREIGN KEY FK_F51E543A8E5EB27B');
        $this->addSql('DROP TABLE cleaning_exception');
        $this->addSql('DROP TABLE cleaning_schedule');
    }
}
