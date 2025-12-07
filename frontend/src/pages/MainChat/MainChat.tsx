import React, { useState } from 'react';
import { Sidebar } from '../../components/Sidebar/Sidebar';
import { ChatArea } from '../../components/ChatArea/ChatArea';
import { UserPanel } from '../../components/UserPanel/UserPanel';
import { NewGroupModal } from '../../components/NewGroupModal/NewGroupModal';
import { NewDirectModal } from '../../components/NewDirectModal/NewDirectModal';
import { ChatSettingsModal } from '../../components/ChatSettingsModal/ChatSettingsModal';
import { useChat, ChatProvider, type Chat } from '@/features/chat';
import { useAuth } from '@/features/auth';
import { mockUsers } from '../../data/mockData';
import styles from './MainChat.module.scss';

const MainChatContent: React.FC = () => {
  const { user } = useAuth();
  const {
    chats,
    selectedChatId,
    messages,
    loadingMessages,
    hasMoreMessages,
    selectChat,
    createGroupChat,
    createDirectChat,
    leaveChat,
    sendMessage,
    loadMoreMessages,
    markAsRead,
  } = useChat();

  const [isGroupModalOpen, setIsGroupModalOpen] = useState(false);
  const [isDirectModalOpen, setIsDirectModalOpen] = useState(false);
  const [isSettingsModalOpen, setIsSettingsModalOpen] = useState(false);
  const [selectedChatForSettings, setSelectedChatForSettings] = useState<Chat | null>(null);

  const selectedChat = chats.find(chat => chat.id === selectedChatId) || null;
  const currentChatMessages = selectedChatId ? messages[selectedChatId] || [] : [];

  const handleSelectChat = async (chatId: number) => {
    await selectChat(chatId);
    await markAsRead(chatId);
  };

  const handleSendMessage = async (chatId: number, text: string) => {
    await sendMessage(chatId, text);
  };

  const handleChatSettings = (chatId: number) => {
    const chat = chats.find(c => c.id === chatId);
    if (chat) {
      setSelectedChatForSettings(chat);
      setIsSettingsModalOpen(true);
    }
  };

  const handleMuteChat = (chatId: number) => {
    // TODO: Implement mute functionality
    setIsSettingsModalOpen(false);
  };

  const handleLeaveChat = async (chatId: number) => {
    await leaveChat(chatId);
    setIsSettingsModalOpen(false);
  };

  const handleAddMembers = (chatId: number) => {
    // TODO: Implement add members modal
    setIsSettingsModalOpen(false);
  };

  const handleCreateGroup = async (name: string, avatar: File | null, userIds: number[]) => {
    try {
      await createGroupChat({
        name,
        description: '',
        memberIds: userIds,
      });
      setIsGroupModalOpen(false);
    } catch (error) {
      console.error('Failed to create group:', error);
    }
  };

  const handleCreateDirect = async (userId: number) => {
    try {
      await createDirectChat(userId);
      setIsDirectModalOpen(false);
    } catch (error) {
      console.error('Failed to create direct chat:', error);
    }
  };

  if (!user) {
    return <div>Loading...</div>;
  }

  return (
    <div className={styles.mainChat}>
      <Sidebar
        chats={chats}
        selectedChatId={selectedChatId}
        onSelectChat={handleSelectChat}
        onMarkAsRead={markAsRead}
        onNewGroupChat={() => setIsGroupModalOpen(true)}
        onNewDirectChat={() => setIsDirectModalOpen(true)}
        onChatSettings={handleChatSettings}
      />

      <ChatArea
        chat={selectedChat}
        messages={currentChatMessages}
        onSendMessage={handleSendMessage}
        loadingMessages={loadingMessages}
        hasMoreMessages={selectedChatId ? hasMoreMessages[selectedChatId] || false : false}
        onLoadMore={() => selectedChatId && loadMoreMessages(selectedChatId)}
      />

      <UserPanel currentUser={user} users={mockUsers} chat={selectedChat} />

      <NewGroupModal
        isOpen={isGroupModalOpen}
        onClose={() => setIsGroupModalOpen(false)}
        currentUserId={user.id}
        onCreateGroup={handleCreateGroup}
      />

      <NewDirectModal
        isOpen={isDirectModalOpen}
        onClose={() => setIsDirectModalOpen(false)}
        currentUserId={user.id}
        onCreateDirect={handleCreateDirect}
      />

      <ChatSettingsModal
        isOpen={isSettingsModalOpen}
        onClose={() => setIsSettingsModalOpen(false)}
        chat={selectedChatForSettings}
        onMuteChat={handleMuteChat}
        onLeaveChat={handleLeaveChat}
        onAddMembers={handleAddMembers}
      />
    </div>
  );
};

// Wrapper component that provides ChatProvider
export const MainChat: React.FC = () => {
  return (
    <ChatProvider>
      <MainChatContent />
    </ChatProvider>
  );
};
