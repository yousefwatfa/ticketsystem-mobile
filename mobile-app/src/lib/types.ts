// Shapes returned by the Laravel API (see docs/native/API.md)

export type TicketStatus = 'open' | 'in_progress' | 'pending' | 'finished' | 'closed';

export type User = {
  id: number;
  username: string;
  fullname: string;
  role: 'Member' | 'Admin' | 'Viewer' | string | null;
  is_admin: boolean;
  is_root: boolean;
  office_location: string | null;
  telegram_linked: boolean;
};

export type Message = {
  id: number;
  content: string;
  author: { id: number | null; fullname: string | null; is_admin: boolean };
  is_mine: boolean;
  created_at: string;
};

export type Attachment = {
  id: number;
  name: string;
  type: string;
  is_image: boolean;
  url: string;
  created_at: string;
};

export type Ticket = {
  id: number;
  title: string;
  description: string;
  status: TicketStatus;
  status_label: string;
  office_location: string | null;
  category?: { id: number; name: string } | null;
  creator?: { id: number; fullname: string } | null;
  assignees?: { id: number; fullname: string }[];
  messages?: Message[];
  attachments?: Attachment[];
  messages_count?: number;
  created_at: string;
  updated_at: string;
  can: {
    reply: boolean;
    attach: boolean;
    confirm: boolean;
    not_fixed: boolean;
    take: boolean;
    finish: boolean;
  };
};

export type StatusCounts = Record<'all' | TicketStatus, number>;

export type Paginated<T> = {
  data: T[];
  counts: StatusCounts;
  meta: { current_page: number; last_page: number; total: number };
};

export type Category = { id: number; name: string };
