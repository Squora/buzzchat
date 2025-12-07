// components/Sidebar/Sidebar.tsx
import React from 'react';
import { Plus, Users, MessageCircle, Search, Settings } from 'lucide-react';
import type { Chat } from '@/features/chat';
import './Sidebar.scss';

interface SidebarProps {
    chats: Chat[];
    selectedChatId: number | null;
    onSelectChat: (chatId: number) => void;
    onMarkAsRead: (chatId: number) => void;
    onNewGroupChat: () => void;
    onNewDirectChat: () => void;
    onChatSettings: (chatId: number) => void;
}

export const Sidebar: React.FC<SidebarProps> = ({
                                                    chats,
                                                    selectedChatId,
                                                    onSelectChat,
                                                    onMarkAsRead,
                                                    onNewGroupChat,
                                                    onNewDirectChat,
                                                    onChatSettings
                                                }) => {
    const [groupsCollapsed, setGroupsCollapsed] = React.useState(false);
    const [directCollapsed, setDirectCollapsed] = React.useState(false);

    const groupChats = chats.filter(chat => chat.type === 'group');
    const directChats = chats.filter(chat => chat.type === 'direct');

    const handleChatClick = (chat: Chat) => {
        onSelectChat(chat.id);
        if (chat.unread) {
            onMarkAsRead(chat.id);
        }
    };

    const handleSettingsClick = (e: React.MouseEvent, chatId: number) => {
        e.stopPropagation();
        onChatSettings(chatId);
    };

    const getDirectChatName = (chat: Chat) => {
        if (chat.type === 'direct' && chat.members && chat.members.length > 0) {
            // Find the other user (not current user)
            const otherMember = chat.members.find(m => m.user.id !== undefined);
            if (otherMember) {
                return otherMember.user.fullName || `${otherMember.user.firstName} ${otherMember.user.lastName}`;
            }
        }
        return chat.name || 'Чат';
    };

    const getDirectChatAvatar = (chat: Chat) => {
        if (chat.type === 'direct' && chat.members && chat.members.length > 0) {
            const otherMember = chat.members.find(m => m.user.id !== undefined);
            if (otherMember) {
                return otherMember.user.firstName?.charAt(0).toUpperCase() +
                       otherMember.user.lastName?.charAt(0).toUpperCase();
            }
        }
        return chat.name?.charAt(0).toUpperCase() || '?';
    };

    const renderChatList = (chats: Chat[]) => {
        if (chats.length === 0) {
            return (
                <div className="empty-section">
                    <p>Нет чатов</p>
                </div>
            );
        }

        return chats.map((chat) => {
            const displayName = chat.type === 'direct' ? getDirectChatName(chat) : chat.name;
            const displayAvatar = chat.type === 'direct' ? getDirectChatAvatar(chat) : chat.name?.charAt(0).toUpperCase() || '?';

            return (
                <div
                    key={chat.id}
                    className={`chat-item ${selectedChatId === chat.id ? 'chat-item--selected' : ''} ${chat.unread ? 'chat-item--unread' : ''}`}
                    onClick={() => handleChatClick(chat)}
                >
                    <div className="chat-item__avatar">
                        <div className="avatar-placeholder small">
                            {chat.photoUrl ? (
                                <img src={chat.photoUrl} alt={displayName || 'Chat'} />
                            ) : (
                                displayAvatar
                            )}
                        </div>
                        {chat.unread && selectedChatId !== chat.id && (
                            <div className="unread-dot" />
                        )}
                    </div>
                    <div className="chat-item__content">
                        <div className="chat-item__header">
                            <span className="chat-item__name">{displayName}</span>
                            <div className="chat-item__actions">
                                <span className="chat-item__time">{chat.lastMessageTime}</span>
                                <button
                                    className="btn btn--icon chat-item__settings"
                                    onClick={(e) => handleSettingsClick(e, chat.id)}
                                    title="Настройки чата"
                                >
                                    <Settings size={16} />
                                </button>
                            </div>
                        </div>
                        <p className="chat-item__last-message">{chat.lastMessage}</p>
                        <div className="chat-item__meta">
                            {chat.type === 'group' && (
                                <span className="chat-item__members">{chat.membersCount} участников</span>
                            )}
                        </div>
                    </div>
                </div>
            );
        });
    };

    return (
        <div className="sidebar">
            {/* Заголовок с поиском */}
            <div className="sidebar__header">
                <div className="sidebar__title">
                    <h2>Сообщения</h2>
                    <span className="badge">{chats.length}</span>
                </div>
                <div className="sidebar__actions">
                    <button
                        className="btn btn--primary btn--new-chat"
                        onClick={onNewGroupChat}
                        title="Создать групповой чат"
                    >
                        <Users size={18} />
                        <span>Группа</span>
                    </button>
                    <button
                        className="btn btn--primary btn--new-chat"
                        onClick={onNewDirectChat}
                        title="Написать сообщение"
                    >
                        <MessageCircle size={18} />
                        <span>Сообщение</span>
                    </button>
                </div>
            </div>

            {/* Поиск */}
            <div className="search-box">
                <Search size={18} />
                <input type="text" placeholder="Поиск чатов и сообщений..." />
            </div>

            {/* Вкладки и соответствующие чаты */}
            <div className="sidebar__content">
                {/* Секция групповых чатов */}
                <div className="tab-section">
                    <div
                        className="tab-header"
                        onClick={() => setGroupsCollapsed(!groupsCollapsed)}
                    >
                        <div className="tab-header__left">
                            <Users size={16} />
                            <span>Групповые чаты</span>
                        </div>
                        <div className="tab-header__right">
                            <span className="tab-count">{groupChats.length}</span>
                            {groupsCollapsed ? (
                                <Plus size={16} />
                            ) : (
                                <MessageCircle size={16} />
                            )}
                        </div>
                    </div>

                    {!groupsCollapsed && (
                        <div className="tab-content">
                            {renderChatList(groupChats)}
                        </div>
                    )}
                </div>

                {/* Секция личных сообщений */}
                <div className="tab-section">
                    <div
                        className="tab-header"
                        onClick={() => setDirectCollapsed(!directCollapsed)}
                    >
                        <div className="tab-header__left">
                            <MessageCircle size={16} />
                            <span>Личные сообщения</span>
                        </div>
                        <div className="tab-header__right">
                            <span className="tab-count">{directChats.length}</span>
                            {directCollapsed ? (
                                <Plus size={16} />
                            ) : (
                                <MessageCircle size={16} />
                            )}
                        </div>
                    </div>

                    {!directCollapsed && (
                        <div className="tab-content">
                            {renderChatList(directChats)}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
};