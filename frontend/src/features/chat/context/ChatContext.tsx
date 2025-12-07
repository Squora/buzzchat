import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
} from "react";
import { chatService } from "../services/chatService";
import { messageService } from "../../message/services/messageService";
import { websocketService } from "../services/websocket";
import { useAuth } from "../../auth";
import type {
  Chat,
  CreateGroupChatRequest,
  CreateDirectChatRequest,
  UpdateChatRequest,
} from "../types/chat.types";
import type { Message, MessageStatus } from "../../message/types/message.types";

interface ChatContextState {
  // Data
  chats: Chat[];
  selectedChatId: number | null;
  messages: Record<number, Message[]>;

  // Loading states
  loading: boolean;
  loadingMessages: boolean;
  sendingMessage: boolean;

  // Pagination
  hasMoreMessages: Record<number, boolean>;

  // Error handling
  error: string | null;

  // Actions - Chats
  selectChat: (chatId: number) => Promise<void>;
  createGroupChat: (
    data: Omit<CreateGroupChatRequest, "member_ids"> & { memberIds: number[] }
  ) => Promise<void>;
  createDirectChat: (userId: number) => Promise<void>;
  updateChat: (chatId: number, data: UpdateChatRequest) => Promise<void>;
  leaveChat: (chatId: number) => Promise<void>;
  deleteChat: (chatId: number) => Promise<void>;

  // Actions - Messages
  sendMessage: (chatId: number, content: string) => Promise<void>;
  loadMoreMessages: (chatId: number) => Promise<void>;
  updateMessage: (messageId: number, content: string) => Promise<void>;
  deleteMessage: (messageId: number) => Promise<void>;
  markAsRead: (chatId: number) => Promise<void>;

  // Utility
  refreshChats: () => Promise<void>;
}

const ChatContext = createContext<ChatContextState | undefined>(undefined);

export const ChatProvider: React.FC<{ children: React.ReactNode }> = ({
  children,
}) => {
  const { user } = useAuth();

  // State
  const [chats, setChats] = useState<Chat[]>([]);
  const [selectedChatId, setSelectedChatId] = useState<number | null>(null);
  const [messages, setMessages] = useState<Record<number, Message[]>>({});
  const [hasMoreMessages, setHasMoreMessages] = useState<
    Record<number, boolean>
  >({});

  const [loading, setLoading] = useState(false);
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [sendingMessage, setSendingMessage] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Load user's chats on mount
  const loadChats = useCallback(async () => {
    if (!user) return;

    try {
      setLoading(true);
      setError(null);
      const chatList = await chatService.getUserChats();
      setChats(chatList);

      // Auto-select first group chat
      if (!selectedChatId && chatList.length > 0) {
        const firstGroupChat =
          chatList.find((c) => c.type === "group") || chatList[0];
        if (firstGroupChat) {
          await selectChat(firstGroupChat.id);
        }
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to load chats");
      console.error("Error loading chats:", err);
    } finally {
      setLoading(false);
    }
  }, [user, selectedChatId]);

  useEffect(() => {
    loadChats();
  }, [loadChats]);

  // Select chat and load its messages
  const selectChat = useCallback(
    async (chatId: number) => {
      try {
        setSelectedChatId(chatId);

        // Load messages if not already loaded
        if (!messages[chatId]) {
          setLoadingMessages(true);
          const response = await messageService.getMessages(chatId, undefined, 50, user?.id);
          setMessages((prev) => ({
            ...prev,
            [chatId]: response.messages.reverse(), // Reverse to show oldest first
          }));
          setHasMoreMessages((prev) => ({
            ...prev,
            [chatId]: response.has_more,
          }));
        }
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to load messages"
        );
        console.error("Error loading messages:", err);
      } finally {
        setLoadingMessages(false);
      }
    },
    [messages]
  );

  // Load more messages (for infinite scroll)
  const loadMoreMessages = useCallback(
    async (chatId: number) => {
      const chatMessages = messages[chatId] || [];
      if (!chatMessages.length || !hasMoreMessages[chatId]) return;

      try {
        setLoadingMessages(true);
        const oldestMessage = chatMessages[0];
        const response = await messageService.getMessages(
          chatId,
          oldestMessage.id,
          50,
          user?.id
        );

        setMessages((prev) => ({
          ...prev,
          [chatId]: [...response.messages.reverse(), ...chatMessages],
        }));

        setHasMoreMessages((prev) => ({
          ...prev,
          [chatId]: response.has_more,
        }));
      } catch (err) {
        console.error("Error loading more messages:", err);
      } finally {
        setLoadingMessages(false);
      }
    },
    [messages, hasMoreMessages, user]
  );

  // Send message with optimistic UI
  const sendMessage = useCallback(
    async (chatId: number, content: string) => {
      if (!user) return;

      const tempId = `temp-${Date.now()}`;
      const optimisticMessage: Message = {
        id: -1,
        chat_id: chatId,
        user_id: user.id,
        user_name: user.name,
        user_avatar: user.avatar,
        content,
        created_at: new Date().toISOString(),
        updated_at: null,
        is_edited: false,
        status: "sending" as MessageStatus,
        isOwn: true,
        tempId,
      };

      // Optimistically add message
      setMessages((prev) => ({
        ...prev,
        [chatId]: [...(prev[chatId] || []), optimisticMessage],
      }));

      try {
        setSendingMessage(true);
        const sentMessage = await messageService.sendMessage({
          chatId: chatId,
          content,
        }, user.id);

        // Replace optimistic message with real one
        setMessages((prev) => ({
          ...prev,
          [chatId]: (prev[chatId] || []).map((msg) =>
            msg.tempId === tempId
              ? { ...sentMessage, status: "sent" as MessageStatus }
              : msg
          ),
        }));

        // Update last message in chat list
        setChats((prev) =>
          prev.map((chat) =>
            chat.id === chatId
              ? {
                  ...chat,
                  lastMessage: content,
                  lastMessageTime: new Date().toLocaleTimeString([], {
                    hour: "2-digit",
                    minute: "2-digit",
                  }),
                }
              : chat
          )
        );
      } catch (err) {
        // Mark message as failed
        setMessages((prev) => ({
          ...prev,
          [chatId]: (prev[chatId] || []).map((msg) =>
            msg.tempId === tempId
              ? { ...msg, status: "failed" as MessageStatus }
              : msg
          ),
        }));
        setError(err instanceof Error ? err.message : "Failed to send message");
        console.error("Error sending message:", err);
      } finally {
        setSendingMessage(false);
      }
    },
    [user]
  );

  // Create group chat
  const createGroupChat = useCallback(
    async (
      data: Omit<CreateGroupChatRequest, "memberIds"> & { memberIds: number[] }
    ) => {
      try {
        setLoading(true);
        const newChat = await chatService.createGroupChat({
          name: data.name,
          description: data.description,
          photo_url: data.photo_url,
          memberIds: data.memberIds,
        });

        setChats((prev) => [newChat, ...prev]);
        await selectChat(newChat.id);
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to create group chat"
        );
        console.error("Error creating group chat:", err);
        throw err;
      } finally {
        setLoading(false);
      }
    },
    [selectChat]
  );

  // Create direct chat
  const createDirectChat = useCallback(
    async (userId: number) => {
      try {
        setLoading(true);
        const chat = await chatService.createDirectChat({ userId: userId });

        // Check if chat already exists
        const existingChat = chats.find((c) => c.id === chat.id);
        if (!existingChat) {
          setChats((prev) => [chat, ...prev]);
        }

        await selectChat(chat.id);
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to create direct chat"
        );
        console.error("Error creating direct chat:", err);
        throw err;
      } finally {
        setLoading(false);
      }
    },
    [chats, selectChat]
  );

  // Update chat
  const updateChat = useCallback(
    async (chatId: number, data: UpdateChatRequest) => {
      try {
        const updatedChat = await chatService.updateChat(chatId, data);
        setChats((prev) =>
          prev.map((chat) => (chat.id === chatId ? updatedChat : chat))
        );
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to update chat");
        console.error("Error updating chat:", err);
        throw err;
      }
    },
    []
  );

  // Leave chat
  const leaveChat = useCallback(
    async (chatId: number) => {
      try {
        await chatService.leaveChat(chatId);
        setChats((prev) => prev.filter((chat) => chat.id !== chatId));

        if (selectedChatId === chatId) {
          const remainingChats = chats.filter((c) => c.id !== chatId);
          if (remainingChats.length > 0) {
            await selectChat(remainingChats[0].id);
          } else {
            setSelectedChatId(null);
          }
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to leave chat");
        console.error("Error leaving chat:", err);
        throw err;
      }
    },
    [chats, selectedChatId, selectChat]
  );

  // Delete chat
  const deleteChat = useCallback(
    async (chatId: number) => {
      try {
        await chatService.deleteChat(chatId);
        setChats((prev) => prev.filter((chat) => chat.id !== chatId));

        if (selectedChatId === chatId) {
          const remainingChats = chats.filter((c) => c.id !== chatId);
          if (remainingChats.length > 0) {
            await selectChat(remainingChats[0].id);
          } else {
            setSelectedChatId(null);
          }
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : "Failed to delete chat");
        console.error("Error deleting chat:", err);
        throw err;
      }
    },
    [chats, selectedChatId, selectChat]
  );

  // Update message
  const updateMessage = useCallback(
    async (messageId: number, content: string) => {
      try {
        const updatedMessage = await messageService.updateMessage(messageId, {
          content,
        }, user?.id);

        // Update message in state
        setMessages((prev) => {
          const newMessages = { ...prev };
          Object.keys(newMessages).forEach((chatIdStr) => {
            const chatId = parseInt(chatIdStr);
            newMessages[chatId] = newMessages[chatId].map((msg) =>
              msg.id === messageId
                ? updatedMessage
                : msg
            );
          });
          return newMessages;
        });
      } catch (err) {
        setError(
          err instanceof Error ? err.message : "Failed to update message"
        );
        console.error("Error updating message:", err);
        throw err;
      }
    },
    [user]
  );

  // Delete message
  const deleteMessage = useCallback(async (messageId: number) => {
    try {
      await messageService.deleteMessage(messageId);

      // Remove message from state
      setMessages((prev) => {
        const newMessages = { ...prev };
        Object.keys(newMessages).forEach((chatIdStr) => {
          const chatId = parseInt(chatIdStr);
          newMessages[chatId] = newMessages[chatId].filter(
            (msg) => msg.id !== messageId
          );
        });
        return newMessages;
      });
    } catch (err) {
      setError(err instanceof Error ? err.message : "Failed to delete message");
      console.error("Error deleting message:", err);
      throw err;
    }
  }, []);

  // Mark messages as read
  const markAsRead = useCallback(
    async (chatId: number) => {
      const chatMessages = messages[chatId] || [];
      const unreadMessageIds = chatMessages
        .filter((msg) => !msg.isOwn)
        .map((msg) => msg.id)
        .filter((id) => id > 0); // Exclude optimistic messages

      if (unreadMessageIds.length === 0) return;

      try {
        await messageService.markAsRead({ message_ids: unreadMessageIds });

        // Update chat unread status
        setChats((prev) =>
          prev.map((chat) =>
            chat.id === chatId ? { ...chat, unread: false } : chat
          )
        );
      } catch (err) {
        console.error("Error marking messages as read:", err);
      }
    },
    [messages]
  );

  // Refresh chats list
  const refreshChats = useCallback(async () => {
    await loadChats();
  }, [loadChats]);

  // WebSocket setup (commented out - will be activated when gogate is ready)
  // useEffect(() => {
  //   if (!user) return;
  //
  //   websocketService.connect(config.websocketUrl);
  //
  //   const unsubscribe = websocketService.subscribe((message) => {
  //     switch (message.type) {
  //       case 'new_message':
  //         // Handle new message from other users
  //         break;
  //       case 'message_updated':
  //         // Handle message update
  //         break;
  //       case 'message_deleted':
  //         // Handle message deletion
  //         break;
  //       case 'chat_updated':
  //         // Handle chat update
  //         break;
  //     }
  //   });
  //
  //   return () => {
  //     unsubscribe();
  //     websocketService.disconnect();
  //   };
  // }, [user]);

  const value: ChatContextState = {
    chats,
    selectedChatId,
    messages,
    loading,
    loadingMessages,
    sendingMessage,
    hasMoreMessages,
    error,
    selectChat,
    createGroupChat,
    createDirectChat,
    updateChat,
    leaveChat,
    deleteChat,
    sendMessage,
    loadMoreMessages,
    updateMessage,
    deleteMessage,
    markAsRead,
    refreshChats,
  };

  return <ChatContext.Provider value={value}>{children}</ChatContext.Provider>;
};

export const useChat = () => {
  const context = useContext(ChatContext);
  if (!context) {
    throw new Error("useChat must be used within ChatProvider");
  }
  return context;
};
