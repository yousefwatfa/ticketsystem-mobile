import Ionicons from '@expo/vector-icons/Ionicons';
import type { ComponentProps, ReactNode } from 'react';
import {
  ActivityIndicator,
  Pressable,
  StyleSheet,
  Text,
  TextInput,
  View,
  type StyleProp,
  type TextInputProps,
  type ViewStyle,
} from 'react-native';

import { colors, radius, space, statusStyle, touch } from '@/lib/theme';
import type { TicketStatus } from '@/lib/types';

type IconName = ComponentProps<typeof Ionicons>['name'];

type ButtonProps = {
  title: string;
  onPress: () => void;
  variant?: 'primary' | 'secondary' | 'danger' | 'success';
  icon?: IconName;
  loading?: boolean;
  disabled?: boolean;
  style?: ViewStyle;
};

export function Button({ title, onPress, variant = 'primary', icon, loading, disabled, style }: ButtonProps) {
  const palette = {
    primary: { bg: colors.primary, fg: '#FFFFFF' },
    secondary: { bg: colors.primarySoft, fg: colors.primary },
    danger: { bg: colors.dangerSoft, fg: colors.danger },
    success: { bg: colors.success, fg: '#FFFFFF' },
  }[variant];
  const inactive = disabled || loading;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityState={{ disabled: !!inactive, busy: !!loading }}
      onPress={onPress}
      disabled={inactive}
      style={({ pressed }) => [
        styles.button,
        { backgroundColor: palette.bg, opacity: inactive ? 0.55 : pressed ? 0.8 : 1 },
        style,
      ]}>
      {loading ? (
        <ActivityIndicator color={palette.fg} />
      ) : (
        <>
          {icon ? <Ionicons name={icon} size={20} color={palette.fg} /> : null}
          <Text style={[styles.buttonText, { color: palette.fg }]}>{title}</Text>
        </>
      )}
    </Pressable>
  );
}

export function Field({ label, error, ...props }: TextInputProps & { label: string; error?: string }) {
  return (
    <View style={styles.field}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        placeholderTextColor={colors.muted}
        accessibilityLabel={label}
        {...props}
        style={[styles.input, props.multiline && styles.multiline, error ? styles.inputError : null, props.style]}
      />
      {error ? <Text style={styles.errorText}>{error}</Text> : null}
    </View>
  );
}

export function Card({ children, style }: { children: ReactNode; style?: StyleProp<ViewStyle> }) {
  return <View style={[styles.card, style]}>{children}</View>;
}

export function StatusBadge({ status, label }: { status: TicketStatus; label: string }) {
  const s = statusStyle[status] ?? statusStyle.pending;
  return (
    <View style={[styles.badge, { backgroundColor: s.bg }]}>
      <Text style={[styles.badgeText, { color: s.fg }]}>{label}</Text>
    </View>
  );
}

export function Banner({ message, tone = 'danger', icon }: { message: string; tone?: 'danger' | 'info'; icon?: IconName }) {
  const isDanger = tone === 'danger';
  return (
    <View
      accessibilityRole="alert"
      style={[styles.banner, { backgroundColor: isDanger ? colors.dangerSoft : colors.primarySoft }]}>
      <Ionicons
        name={icon ?? (isDanger ? 'alert-circle' : 'information-circle')}
        size={22}
        color={isDanger ? colors.danger : colors.primary}
      />
      <Text style={[styles.bannerText, { color: isDanger ? colors.danger : colors.primary }]}>{message}</Text>
    </View>
  );
}

export function EmptyState({ icon, title, children }: { icon: IconName; title: string; children?: ReactNode }) {
  return (
    <View style={styles.empty}>
      <Ionicons name={icon} size={48} color={colors.muted} />
      <Text style={styles.emptyTitle}>{title}</Text>
      {children}
    </View>
  );
}

export function SectionTitle({ children }: { children: ReactNode }) {
  return <Text style={styles.sectionTitle}>{children}</Text>;
}

const styles = StyleSheet.create({
  button: {
    minHeight: touch,
    borderRadius: radius.md,
    paddingHorizontal: space.lg,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: space.sm,
  },
  buttonText: { fontSize: 16, fontWeight: '700' },
  field: { marginBottom: space.lg },
  label: { fontSize: 15, fontWeight: '700', color: colors.text, marginBottom: space.sm },
  input: {
    minHeight: touch,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    backgroundColor: colors.surface,
    paddingHorizontal: space.md,
    fontSize: 16,
    color: colors.text,
  },
  multiline: { minHeight: 120, paddingTop: space.md, textAlignVertical: 'top' },
  inputError: { borderColor: colors.danger },
  errorText: { color: colors.danger, fontSize: 13, marginTop: space.xs, writingDirection: 'rtl' },
  card: {
    backgroundColor: colors.surface,
    borderRadius: radius.lg,
    borderWidth: 1,
    borderColor: colors.border,
    padding: space.lg,
  },
  badge: { borderRadius: radius.pill, paddingHorizontal: space.md, paddingVertical: 4, alignSelf: 'flex-start' },
  badgeText: { fontSize: 13, fontWeight: '700' },
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: space.sm,
    padding: space.md,
    borderRadius: radius.md,
    marginBottom: space.md,
  },
  bannerText: { flex: 1, fontSize: 14, fontWeight: '600' },
  empty: { alignItems: 'center', paddingVertical: 48, gap: space.md },
  emptyTitle: { fontSize: 16, color: colors.muted, fontWeight: '600' },
  sectionTitle: { fontSize: 17, fontWeight: '800', color: colors.text, marginBottom: space.md },
});
