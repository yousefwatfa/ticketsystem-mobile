import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';

import { StatusBadge } from '@/components/ui';
import { colors, radius, space, statusFilters, timeAgo } from '@/lib/theme';
import type { StatusCounts, Ticket, TicketStatus } from '@/lib/types';

export function TicketCard({ ticket, showCreator }: { ticket: Ticket; showCreator?: boolean }) {
  const assignees = ticket.assignees?.map((a) => a.fullname).join('، ');

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`تذكرة ${ticket.id}: ${ticket.title}، الحالة ${ticket.status_label}`}
      onPress={() => router.push({ pathname: '/ticket/[id]', params: { id: String(ticket.id) } })}
      style={({ pressed }) => [styles.card, pressed && styles.cardPressed]}>
      <View style={styles.row}>
        <Text style={styles.id}>#{ticket.id}</Text>
        <StatusBadge status={ticket.status} label={ticket.status_label} />
      </View>
      <Text style={styles.title} numberOfLines={2}>
        {ticket.title}
      </Text>
      <View style={styles.meta}>
        {ticket.category ? <Meta icon="pricetag-outline" text={ticket.category.name} /> : null}
        <Meta icon="time-outline" text={timeAgo(ticket.created_at)} />
        {showCreator && ticket.creator ? <Meta icon="person-outline" text={ticket.creator.fullname} /> : null}
        {assignees ? <Meta icon="person-circle-outline" text={assignees} /> : null}
        {ticket.messages_count ? <Meta icon="chatbubble-outline" text={String(ticket.messages_count)} /> : null}
      </View>
    </Pressable>
  );
}

function Meta({ icon, text }: { icon: React.ComponentProps<typeof Ionicons>['name']; text: string }) {
  return (
    <View style={styles.metaItem}>
      <Ionicons name={icon} size={14} color={colors.muted} />
      <Text style={styles.metaText} numberOfLines={1}>
        {text}
      </Text>
    </View>
  );
}

export function StatusChips({
  value,
  counts,
  onChange,
}: {
  value: 'all' | TicketStatus;
  counts?: StatusCounts;
  onChange: (value: 'all' | TicketStatus) => void;
}) {
  return (
    // fixed-height wrapper: a horizontal ScrollView otherwise stretches/shrinks inside a flex column
    <View style={styles.chipsBar}>
    <ScrollView
      horizontal
      showsHorizontalScrollIndicator={false}
      contentContainerStyle={styles.chips}
      accessibilityRole="tablist">
      {statusFilters.map((f) => {
        const selected = f.key === value;
        return (
          <Pressable
            key={f.key}
            accessibilityRole="tab"
            accessibilityState={{ selected }}
            onPress={() => onChange(f.key)}
            style={[styles.chip, selected && styles.chipSelected]}>
            <Text style={[styles.chipText, selected && styles.chipTextSelected]}>
              {f.label}
              {counts ? ` (${counts[f.key] ?? 0})` : ''}
            </Text>
          </Pressable>
        );
      })}
    </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: space.lg,
    marginBottom: space.md,
    gap: space.sm,
  },
  cardPressed: { backgroundColor: colors.background },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  id: { color: colors.muted, fontWeight: '700' },
  title: { fontSize: 16, fontWeight: '800', color: colors.text },
  meta: { flexDirection: 'row', flexWrap: 'wrap', gap: space.md },
  metaItem: { flexDirection: 'row', alignItems: 'center', gap: 4, maxWidth: '100%' },
  metaText: { color: colors.muted, fontSize: 13 },
  chipsBar: { height: 64, flexShrink: 0 },
  chips: { gap: space.sm, paddingVertical: space.md, paddingHorizontal: space.lg, alignItems: 'center' },
  chip: {
    minHeight: 40,
    paddingHorizontal: space.lg,
    borderRadius: radius.pill,
    borderWidth: 1,
    borderColor: colors.border,
    backgroundColor: colors.surface,
    justifyContent: 'center',
  },
  chipSelected: { backgroundColor: colors.primary, borderColor: colors.primary },
  chipText: { color: colors.muted, fontWeight: '700', fontSize: 14 },
  chipTextSelected: { color: '#FFFFFF' },
});
