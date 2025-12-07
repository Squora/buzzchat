import React, { useState, useEffect, useRef } from "react";
import {
  Send,
  Paperclip,
  Smile,
  MoreVertical,
  MessageCircle,
  Loader2,
} from "lucide-react";
import EmojiPicker, { type EmojiClickData } from "emoji-picker-react";
import type { Chat } from "@/features/chat";
import type { Message } from "@/features/message";
import { useInfiniteScroll } from "@/features/chat";
import { MessageAttachments } from "./MessageAttachments";
import "./ChatArea.scss";

interface ChatAreaProps {
  chat: Chat | null;
  messages: Message[];
  onSendMessage: (chatId: number, text: string) => void;
  loadingMessages?: boolean;
  hasMoreMessages?: boolean;
  onLoadMore?: () => void;
}

export const ChatArea: React.FC<ChatAreaProps> = ({
  chat,
  messages,
  onSendMessage,
  loadingMessages = false,
  hasMoreMessages = false,
  onLoadMore,
}) => {
  const [newMessage, setNewMessage] = useState("");
  const [showEmojiPicker, setShowEmojiPicker] = useState(false);
  const [selectedFiles, setSelectedFiles] = useState<File[]>([]);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const emojiPickerRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const { sentinelRef, scrollContainerRef, restoreScrollPosition } =
    useInfiniteScroll({
      onLoadMore: onLoadMore || (() => {}),
      hasMore: hasMoreMessages,
      loading: loadingMessages,
    });

  // Close emoji picker when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (
        emojiPickerRef.current &&
        !emojiPickerRef.current.contains(event.target as Node)
      ) {
        setShowEmojiPicker(false);
      }
    };

    if (showEmojiPicker) {
      document.addEventListener("mousedown", handleClickOutside);
    }

    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, [showEmojiPicker]);

  const handleSendMessage = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newMessage.trim() || !chat) return;

    onSendMessage(chat.id, newMessage);
    setNewMessage("");
    setSelectedFiles([]);
  };

  const handleEmojiClick = (emojiData: EmojiClickData) => {
    const input = inputRef.current;
    if (!input) return;

    const start = input.selectionStart || 0;
    const end = input.selectionEnd || 0;
    const text = newMessage;
    const before = text.substring(0, start);
    const after = text.substring(end);

    setNewMessage(before + emojiData.emoji + after);
    setShowEmojiPicker(false);

    // Restore cursor position after emoji insertion
    setTimeout(() => {
      const newPosition = start + emojiData.emoji.length;
      input.setSelectionRange(newPosition, newPosition);
      input.focus();
    }, 0);
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const files = e.target.files;
    if (files) {
      setSelectedFiles(Array.from(files));
    }
  };

  const handleAttachmentClick = () => {
    fileInputRef.current?.click();
  };

  const handleRemoveFile = (index: number) => {
    setSelectedFiles((prev) => prev.filter((_, i) => i !== index));
  };

  if (!chat) {
    return (
      <div className="chat-area chat-area--empty">
        <div className="empty-state">
          <MessageCircle size={64} />
          <h3>Выберите чат чтобы начать общение</h3>
          <p>Выберите существующий чат или создайте новый</p>
        </div>
      </div>
    );
  }

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
    <div className="chat-area">
      {/* Заголовок чата */}
      <div className="chat-area__header">
        <div className="chat-info">
          <div className="chat-avatar-group">
            <div className="avatar-placeholder primary">
              {chat.photoUrl ? (
                <img src={chat.photoUrl} alt={displayName} />
              ) : (
                displayAvatar
              )}
            </div>
          </div>
          <div className="chat-details">
            <h3>{displayName}</h3>
            <span className="chat-status">
              {chat.type === "group"
                ? `${chat.membersCount} участников`
                : "Онлайн"}
            </span>
          </div>
        </div>
        <div className="chat-actions">
          <button className="btn btn--icon">
            <MoreVertical size={20} />
          </button>
        </div>
      </div>

      {/* Лента сообщений */}
      <div className="chat-area__messages" ref={scrollContainerRef}>
        {/* Sentinel для infinite scroll */}
        {hasMoreMessages && (
          <div
            ref={sentinelRef}
            style={{ height: "1px", marginBottom: "10px" }}
          />
        )}

        {/* Loading indicator */}
        {loadingMessages && (
          <div className="loading-indicator">
            <Loader2 className="spinning" size={24} />
            <span>Загрузка сообщений...</span>
          </div>
        )}

        <div className="date-divider">
          <span>Сегодня</span>
        </div>

        {messages.map((message) => {
          const messageKey = message.tempId || `msg-${message.id}`;
          const formattedTime = message.created_at
            ? new Date(message.created_at).toLocaleTimeString("ru-RU", {
                hour: "2-digit",
                minute: "2-digit",
              })
            : "";

          return (
            <div
              key={messageKey}
              className={`message ${message.isOwn ? "message--own" : ""} ${
                message.status === "failed" ? "message--failed" : ""
              }`}
            >
              {!message.isOwn && (
                <div className="message__avatar">
                  <div className="avatar-placeholder small">
                    {message.user_avatar ? (
                      <img src={message.user_avatar} alt={message.user_name} />
                    ) : (
                      message.user_name?.charAt(0).toUpperCase() || "?"
                    )}
                  </div>
                </div>
              )}
              <div className="message__content">
                {!message.isOwn && (
                  <div className="message__sender">{message.user_name}</div>
                )}
                <div className="message__bubble">
                  {message.content && <p>{message.content}</p>}
                  <MessageAttachments attachments={message.attachments || []} />
                  {message.status === "sending" && (
                    <span className="message__status">Отправка...</span>
                  )}
                  {message.status === "failed" && (
                    <span className="message__status message__status--error">
                      Не отправлено
                    </span>
                  )}
                </div>
                <div className="message__time">{formattedTime}</div>
              </div>
            </div>
          );
        })}
      </div>

      {/* Поле ввода */}
      <form className="chat-area__input-form" onSubmit={handleSendMessage}>
        <div className="input-actions">
          <button
            type="button"
            className="btn btn--icon"
            onClick={handleAttachmentClick}
            title="Прикрепить файл"
          >
            <Paperclip size={20} />
          </button>
          <div className="emoji-picker-wrapper" ref={emojiPickerRef}>
            <button
              type="button"
              className="btn btn--icon"
              onClick={() => setShowEmojiPicker(!showEmojiPicker)}
              title="Вставить эмодзи"
            >
              <Smile size={20} />
            </button>
            {showEmojiPicker && (
              <div className="emoji-picker-container">
                <EmojiPicker onEmojiClick={handleEmojiClick} />
              </div>
            )}
          </div>
        </div>

        {/* Hidden file input */}
        <input
          ref={fileInputRef}
          type="file"
          multiple
          onChange={handleFileSelect}
          style={{ display: "none" }}
          accept="image/*,video/*,.pdf,.doc,.docx,.txt"
        />

        {/* File preview */}
        {selectedFiles.length > 0 && (
          <div className="selected-files">
            {selectedFiles.map((file, index) => (
              <div key={index} className="file-chip">
                <span className="file-name">{file.name}</span>
                <button
                  type="button"
                  onClick={() => handleRemoveFile(index)}
                  className="file-remove"
                >
                  ×
                </button>
              </div>
            ))}
          </div>
        )}

        <input
          ref={inputRef}
          type="text"
          value={newMessage}
          onChange={(e) => setNewMessage(e.target.value)}
          placeholder="Напишите сообщение..."
          className="chat-area__input"
        />
        <button
          type="submit"
          className="btn btn--primary btn--send"
          disabled={!newMessage.trim() && selectedFiles.length === 0}
        >
          <Send size={18} />
        </button>
      </form>
    </div>
  );
};
