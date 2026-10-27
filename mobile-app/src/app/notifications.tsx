import Ionicons from '@expo/vector-icons/Ionicons';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';

import { Banner, EmptyState } from '@/components/ui';
import { api, ApiError } from '@/lib/api';
import { colors, radius, space, timeAgo } from '@/lib/theme';
import { unread } from '@/lib/unread';

type Item = { id: string; message: string; ticket_id: number | null; read: boolean; created_at: string };

export default function NotificationsScreen() {
  const [items, setItems] = useState<Item[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);

  const load = useCallback(async () => {
    try {
      const result = await api<{ unread: number; data: Item[] }>('/notifications');
      setItems(result.data);
      setError(null);
      // opening the list counts as reading it (unread items stay highlighted until next visit)
      if (result.unread > 0) {
        await api('/notifications/read', { method: 'POST' });
      }
      unread.set(0);
    } catch (e) {
      setError(e as ApiError);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  return (
    <FlatList
      data={items}
      keyExtractor={(n) => n.id}
      contentContainerStyle={styles.list}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} colors={[colors.primary]} />}
      ListHeaderComponent={error ? <Banner message={error.message} icon={error.offline ? 'wifi-outline' : undefined} /> : undefined}
      ListEmptyComponent={
        loading ? (
          <ActivityIndicator style={styles.loader} size="large" color={colors.primary} />
        ) : error ? undefined : (
          <EmptyState icon="notifications-off-outline" title="لا توجد إشعارات" />
        )
      }
      renderItem={({ item }) => (
        <Pressable
          accessibilityRole={item.ticket_id ? 'button' : 'text'}
          accessibilityLabel={`${item.read ? '' : 'جديد: '}${item.message}`}
          disabled={!item.ticket_id}
          onPress={() => item.ticket_id && router.push({ pathname: '/ticket/[id]', params: { id: String(item.ticket_id) } })}
          style={({ pressed }) => [styles.item, !item.read && styles.unread, pressed && styles.pressed]}>
          <View style={[styles.dot, !item.read && styles.dotUnread]} />
          <View style={styles.body}>
            <Text style={styles.message}>{item.message}</Text>
            <Text style={styles.time}>{timeAgo(item.created_at)}</Text>
          </View>
          {item.ticket_id ? <Ionicons name="chevron-back" size={18} color={colors.muted} /> : null}
        </Pressable>
      )}
    />
  );
}

const styles = StyleSheet.create({
  list: { padding: space.lg, flexGrow: 1 },
  loader: { marginTop: 48 },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.md,
    padding: space.lg,
    marginBottom: space.sm,
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
  },
  unread: { backgroundColor: colors.primarySoft, borderColor: '#B3DAF5' },
  pressed: { opacity: 0.75 },
  dot: { width: 10, height: 10, borderRadius: 5, backgroundColor: 'transparent' },
  dotUnread: { backgroundColor: colors.primary },
  body: { flex: 1, gap: 4 },
  message: { fontSize: 15, lineHeight: 22, color: colors.text },
  time: { fontSize: 12, color: colors.muted },
});
