import Ionicons from '@expo/vector-icons/Ionicons';
import { useEffect, useState } from 'react';
import { ActivityIndicator, FlatList, RefreshControl, StyleSheet, TextInput, View } from 'react-native';

import { StatusChips, TicketCard } from '@/components/tickets';
import { Banner, EmptyState } from '@/components/ui';
import { colors, radius, space, touch } from '@/lib/theme';
import { useTicketList } from '@/lib/useTicketList';

export default function DepartmentTicketsScreen() {
  const [input, setInput] = useState('');
  const [search, setSearch] = useState('');
  const list = useTicketList('/admin/tickets', search);

  // wait until typing pauses before querying
  useEffect(() => {
    const t = setTimeout(() => setSearch(input), 400);
    return () => clearTimeout(t);
  }, [input]);

  return (
    <View style={styles.screen}>
      <View style={styles.searchBox}>
        <Ionicons name="search" size={20} color={colors.muted} />
        <TextInput
          value={input}
          onChangeText={setInput}
          placeholder="ابحث برقم التذكرة أو العنوان أو اسم الموظف"
          placeholderTextColor={colors.muted}
          style={styles.searchInput}
          accessibilityLabel="بحث في التذاكر"
          returnKeyType="search"
          autoCorrect={false}
        />
        {input ? <Ionicons name="close-circle" size={20} color={colors.muted} onPress={() => setInput('')} accessibilityLabel="مسح البحث" /> : null}
      </View>

      <StatusChips value={list.status} counts={list.counts} onChange={list.setStatus} />

      <FlatList
        data={list.tickets}
        keyExtractor={(t) => String(t.id)}
        renderItem={({ item }) => <TicketCard ticket={item} showCreator />}
        contentContainerStyle={styles.list}
        refreshControl={<RefreshControl refreshing={list.refreshing} onRefresh={list.refresh} colors={[colors.primary]} />}
        onEndReached={list.loadMore}
        onEndReachedThreshold={0.4}
        keyboardShouldPersistTaps="handled"
        ListHeaderComponent={
          list.error ? <Banner message={list.error.message} icon={list.error.offline ? 'wifi-outline' : undefined} /> : undefined
        }
        ListEmptyComponent={
          list.loading ? (
            <ActivityIndicator style={styles.loader} color={colors.primary} size="large" />
          ) : list.error ? undefined : (
            <EmptyState icon="checkmark-done-circle-outline" title={search ? 'لا توجد نتائج' : 'لا توجد تذاكر في قسمك'} />
          )
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  screen: { flex: 1, backgroundColor: colors.background },
  searchBox: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.sm,
    marginHorizontal: space.lg,
    marginTop: space.md,
    paddingHorizontal: space.md,
    minHeight: touch,
    backgroundColor: colors.surface,
    borderRadius: radius.md,
    borderWidth: 1,
    borderColor: colors.border,
  },
  searchInput: { flex: 1, fontSize: 15, color: colors.text, minHeight: touch },
  list: { padding: space.lg, paddingTop: space.sm, flexGrow: 1 },
  loader: { marginTop: 48 },
});
