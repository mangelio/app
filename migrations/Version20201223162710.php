<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201223162710 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE authentication_token');
        $this->addSql('ALTER TABLE construction_manager ADD authentication_token LONGTEXT');
        $this->addSql('ALTER TABLE craftsman ADD authentication_token LONGTEXT');
        $this->addSql('ALTER TABLE filter ADD access_allowed_before DATETIME DEFAULT NULL, ADD authentication_token LONGTEXT');

        $this->addSql('UPDATE construction_manager SET authentication_token = UUID()');
        $this->addSql('UPDATE craftsman SET authentication_token = UUID()');
        $this->addSql('UPDATE filter SET authentication_token = UUID()');

        $this->addSql('ALTER TABLE construction_manager MODIFY authentication_token LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE craftsman MODIFY authentication_token LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE filter MODIFY authentication_token LONGTEXT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE authentication_token (id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', construction_manager_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', filter_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', craftsman_id CHAR(36) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:guid)\', token LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, last_changed_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, access_allowed_before DATETIME DEFAULT NULL, INDEX IDX_B54C4ADD34508F72 (craftsman_id), INDEX IDX_B54C4ADDA69C9147 (construction_manager_id), INDEX IDX_B54C4ADDD395B25E (filter_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE authentication_token ADD CONSTRAINT FK_B54C4ADD34508F72 FOREIGN KEY (craftsman_id) REFERENCES craftsman (id)');
        $this->addSql('ALTER TABLE authentication_token ADD CONSTRAINT FK_B54C4ADDA69C9147 FOREIGN KEY (construction_manager_id) REFERENCES construction_manager (id)');
        $this->addSql('ALTER TABLE authentication_token ADD CONSTRAINT FK_B54C4ADDD395B25E FOREIGN KEY (filter_id) REFERENCES filter (id)');
        $this->addSql('ALTER TABLE construction_manager DROP authentication_token');
        $this->addSql('ALTER TABLE craftsman DROP authentication_token');
        $this->addSql('ALTER TABLE filter DROP access_allowed_before, DROP authentication_token');
    }
}
