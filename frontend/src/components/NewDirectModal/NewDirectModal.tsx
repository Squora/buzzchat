// components/NewDirectModal/NewDirectModal.tsx
import React, { useState, useEffect } from "react";
import { X, Search, User as UserIcon, Loader2 } from "lucide-react";
import { useInfiniteUsers } from "@/features/user/hooks/useInfiniteUsers";
import type { User } from "@/features/user/types/user.types";
import "./NewDirectModal.scss";

interface NewDirectModalProps {
  isOpen: boolean;
  onClose: () => void;
  currentUserId: number;
  onCreateDirect: (userId: number) => void;
}

export const NewDirectModal: React.FC<NewDirectModalProps> = ({
  isOpen,
  onClose,
  currentUserId,
  onCreateDirect,
}) => {
  const [searchTerm, setSearchTerm] = useState("");
  const { users, loading, error, hasMore, search, observerRef } =
    useInfiniteUsers({
      limit: 20,
      filters: { isActive: true },
    });

  // Filter out current user
  const filteredUsers = users.filter((user) => user.id !== currentUserId);

  // Handle search with debounce
  useEffect(() => {
    const timer = setTimeout(() => {
      search(searchTerm);
    }, 300);

    return () => clearTimeout(timer);
  }, [searchTerm]);

  const handleCreateDirect = (user: User) => {
    onCreateDirect(user.id);
    onClose();
  };

  const handleClose = () => {
    setSearchTerm("");
    onClose();
  };

  const getFullName = (user: User) => `${user.firstName} ${user.lastName}`;

  const getAvatarInitials = (user: User) => {
    return `${user.firstName?.charAt(0)}${user.lastName?.charAt(
      0
    )}`.toUpperCase();
  };

  const isUserOnline = (user: User) => {
    return ["available", "busy", "away"].includes(user.onlineStatus);
  };

  if (!isOpen) return null;

  return (
    <div className="modal-overlay" onClick={handleClose}>
      <div
        className="modal-content modal-content--small"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="modal-header">
          <h3>Новое сообщение</h3>
          <button className="btn btn--icon" onClick={handleClose}>
            <X size={20} />
          </button>
        </div>

        <div className="modal-search">
          <Search size={18} />
          <input
            type="text"
            placeholder="Поиск сотрудников..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>

        <div className="modal-body">
          {error && <div className="error-message">{error}</div>}

          <div className="users-list">
            {filteredUsers.map((user, index) => {
              const isLastItem = index === filteredUsers.length - 1;
              return (
                <div
                  key={user.id}
                  ref={isLastItem && hasMore ? observerRef : null}
                  className="user-item"
                  onClick={() => handleCreateDirect(user)}
                >
                  <div className="user-item__avatar">
                    {user.photoUrl ? (
                      <img
                        src={user.photoUrl}
                        alt={getFullName(user)}
                        className="avatar-image small"
                      />
                    ) : (
                      <div className="avatar-placeholder small">
                        {getAvatarInitials(user)}
                      </div>
                    )}
                    {isUserOnline(user) && <div className="online-indicator" />}
                  </div>
                  <div className="user-item__info">
                    <div className="user-item__name">{getFullName(user)}</div>
                    <div className="user-item__role">
                      {user.position || "Сотрудник"}
                    </div>
                  </div>
                  <div className="user-item__icon">
                    <UserIcon size={16} />
                  </div>
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
                {searchTerm
                  ? "Пользователи не найдены"
                  : "Нет доступных пользователей"}
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};
