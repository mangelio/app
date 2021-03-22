<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210322104757 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE construction_manager ADD receive_weekly TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE email ADD json_body TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE construction_manager DROP receive_weekly');
        $this->addSql('ALTER TABLE email DROP json_body');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
