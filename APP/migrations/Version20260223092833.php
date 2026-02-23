<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260223092833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E8E5EB27B');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0E8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES contact (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cleaning_exception DROP FOREIGN KEY FK_93055A0E8E5EB27B');
        $this->addSql('ALTER TABLE cleaning_exception ADD CONSTRAINT FK_93055A0E8E5EB27B FOREIGN KEY (cleaning_id) REFERENCES cleaning (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
