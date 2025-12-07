// src/data/mockData.ts

export interface User {
    id: number;
    name: string;
    role: string;
    avatar: string;
    online: boolean;
    isYou?: boolean;
    email?: string;
}

export interface Chat {
    id: string;
    name: string;
    lastMessage: string;
    unread?: boolean;
    members: number;
    isGroup: boolean;
    lastMessageTime: string;
    avatar?: string;
    type: 'group' | 'direct';
    participants?: string[];
}

export interface Message {
    id: string;
    text: string;
    sender: string;
    senderId: string;
    time: string;
    isOwn?: boolean;
    avatar?: string;
}

export const currentUser: User = {
    id: 1,
    name: 'Алексей Иванов',
    role: 'Frontend Developer',
    avatar: 'АИ',
    online: true,
    isYou: true,
    email: 'alexey@company.com'
};

export const mockUsers: User[] = [
    { id: 2, name: 'Мария Сидорова', role: 'Team Lead', avatar: 'МС', online: true, email: 'maria@company.com' },
    { id: 3, name: 'Петр Васильев', role: 'Backend Developer', avatar: 'ПВ', online: true, email: 'petr@company.com' },
    { id: 4, name: 'Анна Козлова', role: 'UI/UX Designer', avatar: 'АК', online: false, email: 'anna@company.com' },
    { id: 5, name: 'Дмитрий Смирнов', role: 'Product Manager', avatar: 'ДС', online: true, email: 'dmitry@company.com' },
    { id: 6, name: 'Елена Петрова', role: 'QA Engineer', avatar: 'ЕП', online: false, email: 'elena@company.com' },
    { id: 7, name: 'Иван Николаев', role: 'DevOps Engineer', avatar: 'ИН', online: true, email: 'ivan@company.com' },
];

// src/data/mockData.ts
export const mockChats: Chat[] = [
    // Групповые чаты
    {
        id: 'group-1',
        name: 'Команда проекта "Альфа"',
        lastMessage: 'Мария: Готов прототип интерфейса',
        unread: true,
        members: 8,
        isGroup: true,
        lastMessageTime: '15:30',
        avatar: 'КА',
        type: 'group'
    },
    {
        id: 'group-2',
        name: 'Маркетинг',
        lastMessage: 'Обсуждаем новую кампанию...',
        members: 12,
        isGroup: true,
        lastMessageTime: '14:15',
        avatar: 'МК',
        type: 'group'
    },
    {
        id: 'group-3',
        name: 'Дизайн студия',
        lastMessage: 'Анна: Ждем фидбек по макетам',
        members: 5,
        isGroup: true,
        lastMessageTime: '12:20',
        avatar: 'ДС',
        type: 'group'
    },

    // Личные сообщения
    {
        id: 'direct-1',
        name: 'Иван Петров',
        lastMessage: 'Добрый день! Отправил файлы по проекту...',
        members: 2,
        isGroup: false,
        lastMessageTime: '13:45',
        avatar: 'ИП',
        type: 'direct',
        participants: ['current-user', 'user-1']
    },
    {
        id: 'direct-2',
        name: 'Мария Сидорова', // Этот чат второй в списке личных
        lastMessage: 'Завтра в 10:00 созвон по проекту',
        unread: true,
        members: 2,
        isGroup: false,
        lastMessageTime: '11:20',
        avatar: 'МС',
        type: 'direct',
        participants: ['current-user', 'user-2']
    },
    {
        id: 'direct-3',
        name: 'Анна Козлова',
        lastMessage: 'Отправила новые макеты',
        members: 2,
        isGroup: false,
        lastMessageTime: '10:15',
        avatar: 'АК',
        type: 'direct',
        participants: ['current-user', 'user-3']
    },
];

export const mockMessages: Record<string, Message[]> = {
    'group-1': [
        { id: 'msg-1', text: 'Привет всем! Как дела с тасками?', sender: 'Мария Сидорова', senderId: 'user-1', time: '15:25', avatar: 'МС' },
        { id: 'msg-2', text: 'У меня почти готово, сегодня скину готовые компоненты.', sender: 'Вы', senderId: 'current-user', time: '15:28', isOwn: true, avatar: 'АИ' },
    ],
    'direct-1': [
        { id: 'msg-3', text: 'Добрый день! Отправил вам файлы по проекту, посмотрите когда будет время', sender: 'Иван Петров', senderId: 'user-1', time: '13:40', avatar: 'ИП' },
        { id: 'msg-4', text: 'Спасибо, посмотрю сегодня вечером', sender: 'Вы', senderId: 'current-user', time: '13:45', isOwn: true, avatar: 'АИ' },
    ],
    'direct-2': [
        { id: 'msg-5', text: 'Напоминаю про созвон завтра в 10:00', sender: 'Мария Сидорova', senderId: 'user-2', time: '11:15', avatar: 'МС' },
        { id: 'msg-6', text: 'Хорошо, подготовлю отчет', sender: 'Вы', senderId: 'current-user', time: '11:20', isOwn: true, avatar: 'АИ' },
    ],
};