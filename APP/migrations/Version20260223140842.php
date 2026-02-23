<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223140842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E8E5EB27B');
        $this->addSql('DROP INDEX IDX_93055A0E8E5EB27B ON cleaning_exception');
        $this->addSql('ALTER TABLE cleaning_exception CHANGE cleaning_id appointment_id INT NOT NULL');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0EE5B533F9 FOREIGN KEY (appointment_id) REFERENCES appointment (id)');
        $this->addSql('CREATE INDEX IDX_93055A0EE5B533F9 ON cleaning_exception (appointment_id)');
        $this->addSql('ALTER TABLE cleaning_schedule DROP FOREIGN KEY FK_F51E543A8E5EB27B');
        $this->addSql('DROP INDEX IDX_F51E543A8E5EB27B ON cleaning_schedule');
        $this->addSql('ALTER TABLE cleaning_schedule CHANGE cleaning_id cleaning_contact_id INT NOT NULL');
        $this->addSql('ALTER TABLE cleaning_schedule ADD CONSTRAINT FK_F51E543A33F6D97C FOREIGN KEY (cleaning_contact_id) REFERENCES contact (id)');
        $this->addSql('CREATE INDEX IDX_F51E543A33F6D97C ON cleaning_schedule (cleaning_contact_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0EE5B533F9');
        $this->addSql('DROP INDEX IDX_93055A0EE5B533F9 ON cleaning_exception');
        $this->addSql('ALTER TABLE cleaning_exception CHANGE appointment_id cleaning_id INT NOT NULL');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0E8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES contact (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_93055A0E8E5EB27B ON cleaning_exception (cleaning_id)');
        $this->addSql('ALTER TABLE cleaning_schedule DROP FOREIGN KEY FK_F51E543A33F6D97C');
        $this->addSql('DROP INDEX IDX_F51E543A33F6D97C ON cleaning_schedule');
        $this->addSql('ALTER TABLE cleaning_schedule CHANGE cleaning_contact_id cleaning_id INT NOT NULL');
        $this->addSql('ALTER TABLE cleaning_schedule ADD CONSTRAINT FK_F51E543A8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_F51E543A8E5EB27B ON cleaning_schedule (cleaning_id)');
    }
}
