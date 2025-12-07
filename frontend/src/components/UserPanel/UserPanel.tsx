import React from 'react';
import { LogOut, Bell, BellOff, Settings } from 'lucide-react';
import type { User } from '../../data/mockData';
import type { Chat } from '@/features/chat';
import './UserPanel.scss';

interface UserPanelProps {
    currentUser: User;
    users: User[];
    chat: Chat | null;
}

export const UserPanel: React.FC<UserPanelProps> = ({ currentUser, users, chat }) => {
    const [notificationsEnabled, setNotificationsEnabled] = React.useState(true);

    const handleLogout = () => {
        console.log('Logout clicked');
        // Здесь будет логика выхода
    };

    const toggleNotifications = () => {
        setNotificationsEnabled(!notificationsEnabled);
    };

    const displayUsers = chat?.type === 'group'
        ? users
        : users.filter(user => user.id !== currentUser.id).slice(0, 1);

    return (
        <div className="user-panel">
            {/* Текущий пользователь */}
            <div className="user-panel__current-user">
                <div className="user-avatar">
                    <div className="avatar-placeholder large">{currentUser.avatar}</div>
                    <div className="online-indicator" />
                </div>
                <div className="user-info">
                    <div className="user-info__name">{currentUser.name}</div>
                    <div className="user-info__role">{currentUser.role}</div>
                </div>
                <div className="user-actions">
                    <button
                        className="btn btn--icon"
                        onClick={toggleNotifications}
                        title={notificationsEnabled ? 'Отключить уведомления' : 'Включить уведомления'}
                    >
                        {notificationsEnabled ? <Bell size={18} /> : <BellOff size={18} />}
                    </button>
                    <button className="btn btn--icon" title="Настройки">
                        <Settings size={18} />
                    </button>
                </div>
            </div>

            {/* Список участников */}
            <div className="user-panel__members">
                <div className="section-header">
                    <h4>{chat ? 'Участники' : 'Все пользователи'}</h4>
                    <span className="member-count">{displayUsers.length}</span>
                </div>
                <div className="members-list">
                    {displayUsers.map((user) => (
                        <div key={user.id} className="member">
                            <div className="member__avatar">
                                <div className="avatar-placeholder small">{user.avatar}</div>
                                {user.online && <div className="online-indicator small" />}
                            </div>
                            <div className="member__info">
                                <div className="member__name">
                                    {user.name}
                                    {user.isYou && <span className="you-badge">Вы</span>}
                                </div>
                                <div className="member__role">{user.role}</div>
                            </div>
                            <div className="member__status">
                                {user.online ? (
                                    <span className="status-online">online</span>
                                ) : (
                                    <span className="status-offline">2 ч. назад</span>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            </div>

            {/* Кнопка выхода */}
            <div className="user-panel__footer">
                <button className="btn btn--logout" onClick={handleLogout}>
                    <LogOut size={18} />
                    Выйти из аккаунта
                </button>
            </div>
        </div>
    );
};