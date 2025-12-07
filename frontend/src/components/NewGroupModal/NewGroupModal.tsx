import React, { useState, useRef, useEffect } from 'react';
import { X, Users, Search, Upload, Image, Loader2 } from 'lucide-react';
import { useInfiniteUsers } from '@/features/user/hooks/useInfiniteUsers';
import type { User } from '@/features/user/types/user.types';
import './NewGroupModal.scss';

interface NewGroupModalProps {
    isOpen: boolean;
    onClose: () => void;
    currentUserId: number;
    onCreateGroup: (name: string, avatar: File | null, userIds: number[]) => void;
}

export const NewGroupModal: React.FC<NewGroupModalProps> = ({
                                                                isOpen,
                                                                onClose,
                                                                currentUserId,
                                                                onCreateGroup
                                                            }) => {
    const [selectedUsers, setSelectedUsers] = useState<User[]>([]);
    const [searchTerm, setSearchTerm] = useState('');
    const [groupName, setGroupName] = useState('');
    const [avatarPreview, setAvatarPreview] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { users, loading, error, hasMore, search, observerRef } = useInfiniteUsers({
        limit: 20,
        filters: { isActive: true },
    });

    // Filter out current user
    const filteredUsers = users.filter(user => user.id !== currentUserId);

    // Handle search with debounce
    useEffect(() => {
        const timer = setTimeout(() => {
            search(searchTerm);
        }, 300);

        return () => clearTimeout(timer);
    }, [searchTerm]);

    const toggleUserSelection = (user: User) => {
        setSelectedUsers(prev =>
            prev.some(u => u.id === user.id)
                ? prev.filter(u => u.id !== user.id)
                : [...prev, user]
        );
    };

    const getFullName = (user: User) => `${user.firstName} ${user.lastName}`;

    const getAvatarInitials = (user: User) => {
        return `${user.firstName[0]}${user.lastName[0]}`.toUpperCase();
    };

    const isUserOnline = (user: User) => {
        return ['available', 'busy', 'away'].includes(user.onlineStatus);
    };

    const handleAvatarUpload = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];
        if (file) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    setAvatarPreview(e.target?.result as string);
                };
                reader.readAsDataURL(file);
            }
        }
    };

    const handleCreateGroup = () => {
        if (!groupName.trim() || selectedUsers.length === 0) return;

        const avatarFile = fileInputRef.current?.files?.[0] || null;
        const userIds = selectedUsers.map(user => user.id);

        onCreateGroup(groupName, avatarFile, userIds);
        handleClose();
    };

    const handleClose = () => {
        setSelectedUsers([]);
        setSearchTerm('');
        setGroupName('');
        setAvatarPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="modal-overlay" onClick={handleClose}>
            <div className="modal-content" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h3>Создать групповой чат</h3>
                    <button className="btn btn--icon" onClick={handleClose}>
                        <X size={20} />
                    </button>
                </div>

                <div className="modal-body">
                    {/* Аватар и название группы */}
                    <div className="group-creation">
                        <div className="avatar-upload">
                            <div className="avatar-upload__preview">
                                {avatarPreview ? (
                                    <img src={avatarPreview} alt="Preview" className="avatar-image" />
                                ) : (
                                    <div className="avatar-placeholder large">
                                        <Image size={24} />
                                    </div>
                                )}
                            </div>
                            <div className="avatar-upload__actions">
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    onChange={handleAvatarUpload}
                                    accept="image/*"
                                    style={{ display: 'none' }}
                                />
                                <button
                                    className="btn btn--secondary btn--upload"
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    <Upload size={16} />
                                    Загрузить аватар
                                </button>
                                <div className="avatar-upload__hint">
                                    JPG, PNG до 2MB
                                </div>
                            </div>
                        </div>

                        <div className="form-group">
                            <label>Название группы *</label>
                            <input
                                type="text"
                                placeholder="Введите название группы..."
                                value={groupName}
                                onChange={(e) => setGroupName(e.target.value)}
                                className="form-input"
                            />
                        </div>
                    </div>

                    {/* Поиск пользователей */}
                    <div className="modal-search">
                        <Search size={18} />
                        <input
                            type="text"
                            placeholder="Поиск сотрудников..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                        />
                    </div>

                    {/* Выбранные пользователи */}
                    {selectedUsers.length > 0 && (
                        <div className="selected-users">
                            <div className="selected-users__label">
                                Участники: {selectedUsers.length}
                                {selectedUsers.length > 0 && ` + вы (${selectedUsers.length + 1} всего)`}
                            </div>
                            <div className="selected-users__list">
                                {selectedUsers.map(user => (
                                    <div key={user.id} className="selected-user">
                                        <div className="selected-user__avatar">
                                            {user.photoUrl ? (
                                                <img src={user.photoUrl} alt={getFullName(user)} className="avatar-image tiny" />
                                            ) : (
                                                getAvatarInitials(user)
                                            )}
                                        </div>
                                        <span className="selected-user__name">{getFullName(user)}</span>
                                        <button
                                            className="btn btn--icon btn--remove"
                                            onClick={() => toggleUserSelection(user)}
                                        >
                                            <X size={14} />
                                        </button>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Ошибка */}
                    {error && (
                        <div className="error-message">
                            {error}
                        </div>
                    )}

                    {/* Список пользователей */}
                    <div className="users-list">
                        {filteredUsers.map((user, index) => {
                            const isLastItem = index === filteredUsers.length - 1;
                            const isSelected = selectedUsers.some(u => u.id === user.id);

                            return (
                                <div
                                    key={user.id}
                                    ref={isLastItem && hasMore ? observerRef : null}
                                    className={`user-item ${isSelected ? 'selected' : ''}`}
                                    onClick={() => toggleUserSelection(user)}
                                >
                                    <div className="user-item__avatar">
                                        {user.photoUrl ? (
                                            <img src={user.photoUrl} alt={getFullName(user)} className="avatar-image small" />
                                        ) : (
                                            <div className="avatar-placeholder small">
                                                {getAvatarInitials(user)}
                                            </div>
                                        )}
                                        {isUserOnline(user) && <div className="online-indicator" />}
                                    </div>
                                    <div className="user-item__info">
                                        <div className="user-item__name">{getFullName(user)}</div>
                                        <div className="user-item__role">{user.position || 'Сотрудник'}</div>
                                    </div>
                                    {isSelected ? (
                                        <div className="user-item__check">✓</div>
                                    ) : (
                                        <div className="user-item__icon">
                                            <Users size={16} />
                                        </div>
                                    )}
                                </div>
                            );
                        })}

                        {loading && (
                            <div className="loading-indicator">
                                <Loader2 size={24} className="spinner" />
                                <span>Загрузка...</span>
                            </div>
                        )}

                        {!loading && filteredUsers.length === 0 && (
                            <div className="empty-state">
                                {searchTerm ? 'Пользователи не найдены' : 'Нет доступных пользователей'}
                            </div>
                        )}
                    </div>
                </div>

                <div className="modal-actions">
                    <button
                        className="btn btn--primary btn--full"
                        onClick={handleCreateGroup}
                        disabled={!groupName.trim() || selectedUsers.length === 0}
                    >
                        Создать группу ({selectedUsers.length + 1} участников)
                    </button>
                </div>
            </div>
        </div>
    );
};