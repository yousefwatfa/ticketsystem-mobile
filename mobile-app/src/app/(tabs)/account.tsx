import Ionicons from '@expo/vector-icons/Ionicons';
import Constants from 'expo-constants';
import type { ComponentProps } from 'react';
import { useState } from 'react';
import { Platform, ScrollView, StyleSheet, Text, View } from 'react-native';

import { Button, Card } from '@/components/ui';
import { getServerUrl } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { colors, space } from '@/lib/theme';

export default function AccountScreen() {
  const { user, signOut, push, enablePush } = useAuth();
  const [retrying, setRetrying] = useState(false);
  const [signingOut, setSigningOut] = useState(false);

  if (!user) return null;

  const pushLabel =
    push === null ? 'جاري التحقق...' : push.state === 'enabled' ? 'مفعّلة' : push.state === 'denied' ? 'غير مسموح بها' : 'غير متاحة';

  const roleLabel = user.is_root ? 'مدير النظام' : user.is_admin ? 'الدعم الفني' : user.role === 'Viewer' ? 'مشاهد' : 'موظف';

  return (
    <ScrollView contentContainerStyle={styles.container}>
      <Card style={styles.profile}>
        <View style={styles.avatar}>
          <Text style={styles.avatarText}>{user.fullname.trim().charAt(0)}</Text>
        </View>
        <Text style={styles.name}>{user.fullname}</Text>
        <Text style={styles.role}>{roleLabel}</Text>
      </Card>

      <Card>
        <Row icon="person-outline" label="اسم المستخدم" value={user.username} />
        <Row icon="location-outline" label="مكان العمل" value={user.office_location ?? '—'} />
        <Row icon="paper-plane-outline" label="تيليجرام" value={user.telegram_linked ? 'مربوط' : 'غير مربوط'} />
        <Row icon="server-outline" label="الخادم" value={getServerUrl()} />
        <Row icon="notifications-outline" label="الإشعارات" value={pushLabel} last />
      </Card>

      {push && push.state !== 'enabled' ? (
        <Card style={styles.pushCard}>
          <Text style={styles.pushText}>
            {push.state === 'denied'
              ? 'الإشعارات غير مسموح بها. فعّلها من إعدادات الهاتف ← التطبيقات ← الدعم الفني ← الإشعارات، ثم اضغط إعادة المحاولة.'
              : push.reason}
          </Text>
          {Platform.OS !== 'web' ? (
            <Button
              title="إعادة المحاولة"
              icon="refresh"
              variant="secondary"
              loading={retrying}
              onPress={async () => {
                setRetrying(true);
                await enablePush();
                setRetrying(false);
              }}
            />
          ) : null}
        </Card>
      ) : null}

      <Button
        title="تسجيل الخروج"
        icon="log-out-outline"
        variant="danger"
        loading={signingOut}
        onPress={async () => {
          setSigningOut(true);
          await signOut();
        }}
      />

      <Text style={styles.version}>الإصدار {Constants.expoConfig?.version ?? '1.0.0'}</Text>
    </ScrollView>
  );
}

function Row({ icon, label, value, last }: { icon: ComponentProps<typeof Ionicons>['name']; label: string; value: string; last?: boolean }) {
  return (
    <View style={[styles.row, !last && styles.rowBorder]}>
      <View style={styles.rowStart}>
        <Ionicons name={icon} size={20} color={colors.muted} />
        <Text style={styles.rowLabel}>{label}</Text>
      </View>
      <Text style={styles.rowValue} numberOfLines={1}>
        {value}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { padding: space.lg, gap: space.lg },
  profile: { alignItems: 'center', gap: space.xs },
  avatar: { width: 72, height: 72, borderRadius: 36, backgroundColor: colors.primarySoft, alignItems: 'center', justifyContent: 'center', marginBottom: space.sm },
  avatarText: { fontSize: 30, fontWeight: '800', color: colors.primary },
  name: { fontSize: 19, fontWeight: '800', color: colors.text },
  role: { fontSize: 14, color: colors.muted },
  row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: space.md, minHeight: 52 },
  rowStart: { flexDirection: 'row', alignItems: 'center', gap: space.md },
  rowBorder: { borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: colors.border },
  rowLabel: { color: colors.muted, fontSize: 14 },
  rowValue: { flexShrink: 1, color: colors.text, fontSize: 15, fontWeight: '600' },
  version: { textAlign: 'center', color: colors.muted, fontSize: 12 },
  pushCard: { gap: space.md, backgroundColor: colors.primarySoft, borderColor: '#B3DAF5' },
  pushText: { color: colors.text, fontSize: 14, lineHeight: 22 },
});
