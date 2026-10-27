import type { TicketStatus } from './types';

/** Colours match the web system; text colours keep ≥ 4.5:1 contrast on white. */
export const colors = {
  primary: '#0077C2',
  primarySoft: '#E8F4FD',
  background: '#F5F8FA',
  surface: '#FFFFFF',
  border: '#E4E6EF',
  text: '#181C32',
  muted: '#5E6278',
  danger: '#C8102E',
  dangerSoft: '#FDECEF',
  success: '#1B7F4B',
  successSoft: '#E8F7EF',
};

export const space = { xs: 4, sm: 8, md: 12, lg: 16, xl: 24 };
export const radius = { sm: 8, md: 12, lg: 16, pill: 999 };
export const touch = 48;

export const statusStyle: Record<TicketStatus, { bg: string; fg: string }> = {
  open: { bg: '#E8F4FD', fg: '#005A94' },
  in_progress: { bg: '#FFF4DE', fg: '#8A5300' },
  pending: { bg: '#EEF0F4', fg: '#41455B' },
  finished: { bg: '#E8F7EF', fg: '#1B7F4B' },
  closed: { bg: '#1B7F4B', fg: '#FFFFFF' },
};

export const statusFilters: { key: 'all' | TicketStatus; label: string }[] = [
  { key: 'all', label: 'الإجمالي' },
  { key: 'open', label: 'جديدة' },
  { key: 'in_progress', label: 'قيد العمل' },
  { key: 'pending', label: 'معلقة' },
  { key: 'finished', label: 'منتهية' },
  { key: 'closed', label: 'مكتملة' },
];

/** "منذ 5 دقائق" style relative time. */
export function timeAgo(iso: string): string {
  const seconds = Math.max(1, Math.round((Date.now() - new Date(iso).getTime()) / 1000));
  if (typeof Intl === 'undefined' || !('RelativeTimeFormat' in Intl)) {
    return new Date(iso).toLocaleString('ar');
  }
  const rtf = new Intl.RelativeTimeFormat('ar', { numeric: 'auto' });
  if (seconds < 60) return rtf.format(-seconds, 'second');
  const minutes = Math.round(seconds / 60);
  if (minutes < 60) return rtf.format(-minutes, 'minute');
  const hours = Math.round(minutes / 60);
  if (hours < 24) return rtf.format(-hours, 'hour');
  const days = Math.round(hours / 24);
  if (days < 30) return rtf.format(-days, 'day');
  return new Date(iso).toLocaleDateString('ar');
}
