// components/ChatSettingsModal/ChatSettingsModal.tsx
import React from 'react';
import { X, Bell, Trash2, UserPlus } from 'lucide-react';
import type { Chat } from '@/features/chat';
import './ChatSettingsModal.scss';

interface ChatSettingsModalProps {
    isOpen: boolean;
    onClose: () => void;
    chat: Chat | null;
    onMuteChat: (chatId: number) => void;
    onLeaveChat: (chatId: number) => void;
    onAddMembers: (chatId: number) => void;
}

export const ChatSettingsModal: React.FC<ChatSettingsModalProps> = ({
                                                                        isOpen,
                                                                        onClose,
                                                                        chat,
                                                                        onMuteChat,
                                                                        onLeaveChat,
                                                                        onAddMembers
                                                                    }) => {
    if (!isOpen || !chat) return null;

    const handleClose = () => {
        onClose();
    };

    const handleMuteClick = () => {
        onMuteChat(chat.id);
    };

    const handleLeaveClick = () => {
        onLeaveChat(chat.id);
    };

    const handleAddMembersClick = () => {
        onAddMembers(chat.id);
    };

    // Get display name and avatar for direct chats
    const getDisplayName = () => {
        if (chat.type === 'direct' && chat.members && chat.members.length > 0) {
            const otherMember = chat.members.find(m => m.user.id !== undefined);
            if (otherMember) {
                return otherMember.user.fullName || `${otherMember.user.firstName} ${otherMember.user.lastName}`;
            }
        }
        return chat.name || 'Чат';
    };

    const getDisplayAvatar = () => {
        if (chat.type === 'direct' && chat.members && chat.members.length > 0) {
            const otherMember = chat.members.find(m => m.user.id !== undefined);
            if (otherMember) {
                return otherMember.user.firstName?.charAt(0).toUpperCase() +
                       otherMember.user.lastName?.charAt(0).toUpperCase();
            }
        }
        return chat.name?.charAt(0).toUpperCase() || '?';
    };

    const displayName = getDisplayName();
    const displayAvatar = getDisplayAvatar();

    return (
        <div className="modal-overlay" onClick={handleClose}>
            <div className="modal-content modal-content--small" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h3>Настройки чата</h3>
                    <button className="btn btn--icon" onClick={handleClose}>
                        <X size={20} />
                    </button>
                </div>

                <div className="modal-body">
                    {/* Информация о чате */}
                    <div className="chat-info-section">
                        <div className="chat-avatar">
                            <div className="avatar-placeholder large">
                                {chat.photoUrl ? (
                                    <img src={chat.photoUrl} alt={displayName} />
                                ) : (
                                    displayAvatar
                                )}
                            </div>
                        </div>
                        <div className="chat-details">
                            <h4>{displayName}</h4>
                            <p>{chat.membersCount} участников</p>
                        </div>
                    </div>

                    {/* Действия */}
                    <div className="settings-actions">
                        {chat.type === 'group' && (
                            <button
                                className="settings-action"
                                onClick={handleAddMembersClick}
                            >
                                <UserPlus size={20} />
                                <span>Добавить участников</span>
                            </button>
                        )}

                        <button
                            className="settings-action"
                            onClick={handleMuteClick}
                        >
                            <Bell size={20} />
                            <span>Отключить уведомления</span>
                        </button>

                        <button
                            className="settings-action settings-action--danger"
                            onClick={handleLeaveClick}
                        >
                            <Trash2 size={20} />
                            <span>{chat.type === 'group' ? 'Покинуть чат' : 'Удалить чат'}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
};