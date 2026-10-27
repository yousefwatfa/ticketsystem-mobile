import Ionicons from '@expo/vector-icons/Ionicons';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { colors, touch } from '@/lib/theme';
import { useUnreadCount } from '@/lib/unread';

/** Header bell with unread badge; opens the notifications screen. */
export function NotificationBell() {
  const count = useUnreadCount();
  const label = count > 0 ? `الإشعارات، ${count} غير مقروءة` : 'الإشعارات';

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={label}
      onPress={() => router.push('/notifications')}
      hitSlop={6}
      style={({ pressed }) => [styles.button, pressed && styles.pressed]}>
      <Ionicons name={count > 0 ? 'notifications' : 'notifications-outline'} size={24} color={colors.text} />
      {count > 0 ? (
        <View style={styles.badge}>
          <Text style={styles.badgeText}>{count > 99 ? '99+' : count}</Text>
        </View>
      ) : null}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  button: { width: touch, height: touch, alignItems: 'center', justifyContent: 'center', marginHorizontal: 4 },
  pressed: { opacity: 0.6 },
  badge: {
    position: 'absolute',
    top: 6,
    end: 4,
    minWidth: 18,
    height: 18,
    paddingHorizontal: 4,
    borderRadius: 9,
    backgroundColor: colors.danger,
    alignItems: 'center',
    justifyContent: 'center',
  },
  badgeText: { color: '#FFFFFF', fontSize: 11, fontWeight: '800' },
});
