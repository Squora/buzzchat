<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251030183155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for optimizing chat members queries with pagination and filters';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX IDX_CHAT_ACTIVE ON chat_members (chat_id, left_at)');
        $this->addSql('CREATE INDEX IDX_CHAT_ROLE_ACTIVE ON chat_members (chat_id, role, left_at)');
        $this->addSql('CREATE INDEX IDX_CHAT_JOINED ON chat_members (chat_id, joined_at)');
        $this->addSql('CREATE INDEX IDX_LEFT_AT ON chat_members (left_at)');
        $this->addSql('ALTER INDEX idx_chats_type RENAME TO IDX_TYPE');
        $this->addSql('ALTER INDEX idx_message_attachments_message_id RENAME TO IDX_MESSAGE');
        $this->addSql('ALTER INDEX idx_message_reactions_message_id RENAME TO IDX_MESSAGE');
        $this->addSql('ALTER INDEX idx_message_reactions_user_id RENAME TO IDX_USER');
        $this->addSql('ALTER INDEX uniq_message_reactions_message_user_emoji RENAME TO UNIQ_MESSAGE_USER_EMOJI');
        $this->addSql('ALTER INDEX idx_message_read_receipts_message_id RENAME TO IDX_MESSAGE');
        $this->addSql('ALTER INDEX idx_message_read_receipts_user_id RENAME TO IDX_USER');
        $this->addSql('ALTER INDEX idx_message_read_receipts_read_at RENAME TO IDX_READ_AT');
        $this->addSql('ALTER INDEX uniq_message_read_receipts_message_user RENAME TO UNIQ_MESSAGE_USER');
        $this->addSql('ALTER INDEX idx_messages_chat_id RENAME TO IDX_DB021E961A9A7125');
        $this->addSql('ALTER INDEX idx_messages_reply_to_id RENAME TO IDX_DB021E96FFDF7169');
        $this->addSql('ALTER INDEX idx_messages_chat_created_at RENAME TO IDX_CHAT_CREATED');
        $this->addSql('ALTER INDEX idx_messages_user_id RENAME TO IDX_USER');
        $this->addSql('ALTER INDEX idx_messages_type RENAME TO IDX_TYPE');
        $this->addSql('ALTER INDEX idx_messages_deleted_at RENAME TO IDX_DELETED');
        $this->addSql('ALTER INDEX uniq_user_settings_user_id_fixed RENAME TO UNIQ_5C844C5A76ED395');
        $this->addSql('CREATE INDEX IDX_NAME_SEARCH ON users (first_name, last_name)');
        $this->addSql('CREATE INDEX IDX_LAST_SEEN ON users (last_seen_at)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX IDX_NAME_SEARCH');
        $this->addSql('DROP INDEX IDX_LAST_SEEN');
        $this->addSql('DROP INDEX IDX_CHAT_ACTIVE');
        $this->addSql('DROP INDEX IDX_CHAT_ROLE_ACTIVE');
        $this->addSql('DROP INDEX IDX_CHAT_JOINED');
        $this->addSql('DROP INDEX IDX_LEFT_AT');
        $this->addSql('ALTER INDEX idx_type RENAME TO idx_chats_type');
        $this->addSql('ALTER INDEX idx_message RENAME TO idx_message_read_receipts_message_id');
        $this->addSql('ALTER INDEX idx_read_at RENAME TO idx_message_read_receipts_read_at');
        $this->addSql('ALTER INDEX idx_user RENAME TO idx_message_read_receipts_user_id');
        $this->addSql('ALTER INDEX uniq_message_user RENAME TO uniq_message_read_receipts_message_user');
        $this->addSql('ALTER INDEX idx_message RENAME TO idx_message_reactions_message_id');
        $this->addSql('ALTER INDEX idx_user RENAME TO idx_message_reactions_user_id');
        $this->addSql('ALTER INDEX uniq_message_user_emoji RENAME TO uniq_message_reactions_message_user_emoji');
        $this->addSql('ALTER INDEX idx_message RENAME TO idx_message_attachments_message_id');
        $this->addSql('ALTER INDEX idx_chat_created RENAME TO idx_messages_chat_created_at');
        $this->addSql('ALTER INDEX idx_db021e961a9a7125 RENAME TO idx_messages_chat_id');
        $this->addSql('ALTER INDEX idx_deleted RENAME TO idx_messages_deleted_at');
        $this->addSql('ALTER INDEX idx_db021e96ffdf7169 RENAME TO idx_messages_reply_to_id');
        $this->addSql('ALTER INDEX idx_type RENAME TO idx_messages_type');
        $this->addSql('ALTER INDEX idx_user RENAME TO idx_messages_user_id');
        $this->addSql('ALTER INDEX uniq_5c844c5a76ed395 RENAME TO uniq_user_settings_user_id_fixed');
    }
}
