import { router } from 'expo-router';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, View } from 'react-native';

import { StatusChips, TicketCard } from '@/components/tickets';
import { Banner, Button, EmptyState } from '@/components/ui';
import { colors, space } from '@/lib/theme';
import { useTicketList } from '@/lib/useTicketList';

export default function MyTicketsScreen() {
  const list = useTicketList('/tickets');

  return (
    <View style={styles.screen}>
      <StatusChips value={list.status} counts={list.counts} onChange={list.setStatus} />

      <FlatList
        data={list.tickets}
        keyExtractor={(t) => String(t.id)}
        renderItem={({ item }) => <TicketCard ticket={item} />}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={list.refreshing} onRefresh={list.refresh} colors={[colors.primary]} />}
        onEndReached={list.loadMore}
        onEndReachedThreshold={0.4}
        ListHeaderComponent={
          list.error ? <Banner message={list.error.message} icon={list.error.offline ? 'wifi-outline' : undefined} /> : undefined
        }
        ListEmptyComponent={
          list.loading ? (
            <ActivityIndicator style={styles.loader} color={colors.primary} size="large" />
          ) : list.error ? undefined : (
            <EmptyState icon="file-tray-outline" title="لا توجد تذاكر">
              <Button title="فتح تذكرة جديدة" icon="add" onPress={() => router.navigate('/new')} />
            </EmptyState>
          )
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  list: { padding: space.lg, paddingTop: space.sm, flexGrow: 1 },
  loader: { marginTop: 48 },
});
