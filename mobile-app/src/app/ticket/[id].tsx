import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { Stack, useFocusEffect, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { useCallback, useRef, useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Banner, Button, Card, SectionTitle, StatusBadge } from '@/components/ui';
import { api, ApiError } from '@/lib/api';
import { confirmAction } from '@/lib/confirm';
import { appendFile, pickPhotos } from '@/lib/images';
import { colors, radius, space, timeAgo, touch } from '@/lib/theme';
import type { Attachment, Message, Ticket } from '@/lib/types';

type Action = 'confirm' | 'not_fixed' | 'take' | 'finish';

const ACTIONS: Record<Action, { path: (id: number) => string; title: string; question: string; done: string }> = {
  confirm: { path: (id) => `/tickets/${id}/confirm`, title: 'تأكيد الحل', question: 'هل تم حل المشكلة فعلاً؟ سيتم إغلاق التذكرة نهائياً.', done: 'تم إغلاق التذكرة. شكراً لك!' },
  not_fixed: { path: (id) => `/tickets/${id}/not-fixed`, title: 'المشكلة مستمرة', question: 'سيتم إعادة التذكرة إلى قيد العمل وإبلاغ الدعم الفني.', done: 'تم إبلاغ الدعم الفني.' },
  take: { path: (id) => `/admin/tickets/${id}/take`, title: 'استلام التذكرة', question: 'سيتم تعيين التذكرة لك وتحويلها إلى قيد العمل.', done: 'تم استلام التذكرة.' },
  finish: { path: (id) => `/admin/tickets/${id}/finish`, title: 'تم الحل', question: 'سيتم تحديد التذكرة كمنتهية بانتظار تأكيد صاحبها.', done: 'تم تحديد التذكرة كمنتهية.' },
};

export default function TicketScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const [ticket, setTicket] = useState<Ticket | null>(null);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [error, setError] = useState<ApiError | null>(null);
  const [notice, setNotice] = useState<string | null>(null);
  const [reply, setReply] = useState('');
  const [sending, setSending] = useState(false);
  const [uploading, setUploading] = useState(false);
  const [acting, setActing] = useState<Action | null>(null);
  const scrollRef = useRef<React.ComponentRef<typeof ScrollView>>(null);
  // Scroll once the new reply is laid out; a fixed timeout fired before the list grew.
  const scrollToEndOnResize = useRef(false);

  const load = useCallback(async () => {
    try {
      const result = await api<{ data: Ticket }>(`/tickets/${id}`);
      setTicket(result.data);
      setError(null);
    } catch (e) {
      setError(e as ApiError);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [id]);

  useFocusEffect(
    useCallback(() => {
      void load();
    }, [load]),
  );

  const sendReply = async () => {
    if (!reply.trim() || !ticket) return;
    setSending(true);
    setNotice(null);
    try {
      const result = await api<{ data: Message }>(`/tickets/${ticket.id}/messages`, { method: 'POST', body: { content: reply.trim() } });
      setTicket({ ...ticket, messages: [...(ticket.messages ?? []), result.data] });
      setReply('');
      scrollToEndOnResize.current = true;
      void load(); // status may change (first admin reply)
    } catch (e) {
      setNotice((e as ApiError).message);
    } finally {
      setSending(false);
    }
  };

  const attachPhoto = async () => {
    if (!ticket) return;
    setUploading(true);
    setNotice(null);
    try {
      const photos = await pickPhotos('camera');
      for (const photo of photos) {
        const form = new FormData();
        await appendFile(form, 'file', photo);
        await api<{ data: Attachment }>(`/tickets/${ticket.id}/attachments`, { method: 'POST', form });
      }
      if (photos.length) await load();
    } catch (e) {
      setNotice((e as Error).message);
    } finally {
      setUploading(false);
    }
  };

  const runAction = async (action: Action) => {
    if (!ticket) return;
    const cfg = ACTIONS[action];
    if (!(await confirmAction(cfg.title, cfg.question, cfg.title))) return;
    setActing(action);
    setNotice(null);
    try {
      await api(cfg.path(ticket.id), { method: 'POST' });
      setNotice(cfg.done);
      await load();
    } catch (e) {
      setNotice((e as ApiError).message);
    } finally {
      setActing(null);
    }
  };

  if (loading) {
    return <ActivityIndicator style={styles.loader} size="large" color={colors.primary} />;
  }

  if (!ticket) {
    return (
      <View style={styles.padded}>
        <Banner message={error?.message ?? 'تعذر تحميل التذكرة.'} icon={error?.offline ? 'wifi-outline' : undefined} />
        <Button title="إعادة المحاولة" icon="refresh" onPress={() => { setLoading(true); void load(); }} />
      </View>
    );
  }

  const images = ticket.attachments?.filter((a) => a.is_image) ?? [];
  const files = ticket.attachments?.filter((a) => !a.is_image) ?? [];

  return (
    <SafeAreaView edges={['bottom']} style={styles.flex}>
      <Stack.Screen options={{ title: `تذكرة #${ticket.id}` }} />
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined} keyboardVerticalOffset={90}>
        <ScrollView
          ref={scrollRef}
          onContentSizeChange={() => {
            if (!scrollToEndOnResize.current) return;
            scrollToEndOnResize.current = false;
            scrollRef.current?.scrollToEnd({ animated: true });
          }}
          contentContainerStyle={styles.container}
          refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} colors={[colors.primary]} />}>
          {notice ? <Banner message={notice} tone="info" /> : null}
          {error ? <Banner message={error.message} icon={error.offline ? 'wifi-outline' : undefined} /> : null}

          <Card style={styles.gap}>
            <View style={styles.row}>
              <StatusBadge status={ticket.status} label={ticket.status_label} />
              <Text style={styles.muted}>{timeAgo(ticket.created_at)}</Text>
            </View>
            <Text style={styles.title}>{ticket.title}</Text>
            <Info icon="pricetag-outline" text={ticket.category?.name ?? '—'} />
            <Info icon="location-outline" text={ticket.office_location ?? '—'} />
            {ticket.creator ? <Info icon="person-outline" text={ticket.creator.fullname} /> : null}
            <Info
              icon="person-circle-outline"
              text={ticket.assignees?.length ? ticket.assignees.map((a) => a.fullname).join('، ') : 'لم تُستلم بعد'}
            />
            <Text style={styles.description}>{ticket.description}</Text>
          </Card>

          {ticket.can.confirm || ticket.can.not_fixed ? (
            <Card style={[styles.gap, styles.highlight]}>
              <Text style={styles.highlightTitle}>هل تم حل المشكلة؟</Text>
              <View style={styles.actions}>
                {ticket.can.confirm ? (
                  <Button title="تأكيد الحل" icon="checkmark-done" variant="success" onPress={() => runAction('confirm')} loading={acting === 'confirm'} style={styles.flex} />
                ) : null}
                {ticket.can.not_fixed ? (
                  <Button title="لم تُحل" icon="refresh" variant="danger" onPress={() => runAction('not_fixed')} loading={acting === 'not_fixed'} style={styles.flex} />
                ) : null}
              </View>
            </Card>
          ) : null}

          {ticket.can.take || ticket.can.finish ? (
            <View style={styles.actions}>
              {ticket.can.take ? (
                <Button title="استلام" icon="hand-left-outline" onPress={() => runAction('take')} loading={acting === 'take'} style={styles.flex} />
              ) : null}
              {ticket.can.finish ? (
                <Button title="تم الحل" icon="checkmark-circle-outline" variant="success" onPress={() => runAction('finish')} loading={acting === 'finish'} style={styles.flex} />
              ) : null}
            </View>
          ) : null}

          {images.length || files.length ? (
            <View>
              <SectionTitle>المرفقات</SectionTitle>
              <View style={styles.gallery}>
                {images.map((a) => (
                  <Pressable key={a.id} accessibilityRole="imagebutton" accessibilityLabel={a.name} onPress={() => WebBrowser.openBrowserAsync(a.url)}>
                    <Image source={{ uri: a.url }} style={styles.thumb} contentFit="cover" />
                  </Pressable>
                ))}
              </View>
              {files.map((a) => (
                <Pressable key={a.id} accessibilityRole="link" onPress={() => WebBrowser.openBrowserAsync(a.url)} style={styles.file}>
                  <Ionicons name="document-attach-outline" size={20} color={colors.primary} />
                  <Text style={styles.fileName} numberOfLines={1}>{a.name}</Text>
                </Pressable>
              ))}
            </View>
          ) : null}

          <View>
            <SectionTitle>المحادثة</SectionTitle>
            {ticket.messages?.length ? (
              ticket.messages.map((m) => <Bubble key={m.id} message={m} />)
            ) : (
              <Text style={styles.muted}>لا توجد ردود بعد.</Text>
            )}
          </View>
        </ScrollView>

        {ticket.can.reply ? (
          <View style={styles.composer}>
            {ticket.can.attach ? (
              <Pressable accessibilityRole="button" accessibilityLabel="إرفاق صورة" onPress={attachPhoto} disabled={uploading} style={styles.iconButton}>
                {uploading ? <ActivityIndicator color={colors.primary} /> : <Ionicons name="camera-outline" size={24} color={colors.primary} />}
              </Pressable>
            ) : null}
            <TextInput
              value={reply}
              onChangeText={setReply}
              placeholder="اكتب ردك هنا..."
              placeholderTextColor={colors.muted}
              multiline
              style={styles.replyInput}
              accessibilityLabel="نص الرد"
            />
            <Pressable
              accessibilityRole="button"
              accessibilityLabel="إرسال الرد"
              onPress={sendReply}
              disabled={sending || !reply.trim()}
              style={[styles.sendButton, (!reply.trim() || sending) && styles.sendDisabled]}>
              {sending ? <ActivityIndicator color="#FFFFFF" /> : <Ionicons name="send" size={20} color="#FFFFFF" style={styles.sendIcon} />}
            </Pressable>
          </View>
        ) : ticket.status === 'closed' ? (
          <View style={styles.closed}>
            <Ionicons name="lock-closed-outline" size={18} color={colors.muted} />
            <Text style={styles.muted}>التذكرة مغلقة، لا يمكن إضافة ردود.</Text>
          </View>
        ) : null}
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

function Info({ icon, text }: { icon: React.ComponentProps<typeof Ionicons>['name']; text: string }) {
  return (
    <View style={styles.info}>
      <Ionicons name={icon} size={18} color={colors.muted} />
      <Text style={styles.infoText}>{text}</Text>
    </View>
  );
}

function Bubble({ message }: { message: Message }) {
  const mine = message.is_mine;
  return (
    <View style={[styles.bubbleWrap, mine ? styles.bubbleMine : styles.bubbleTheirs]}>
      <Text style={styles.author}>
        {mine ? 'أنت' : message.author.fullname}
        {!mine && message.author.is_admin ? ' · الدعم الفني' : ''}
      </Text>
      <View style={[styles.bubble, mine ? styles.bubbleMineBg : styles.bubbleTheirsBg]}>
        <Text style={styles.bubbleText}>{message.content}</Text>
      </View>
      <Text style={styles.time}>{timeAgo(message.created_at)}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  loader: { marginTop: 64 },
  padded: { padding: space.lg, gap: space.md },
  container: { padding: space.lg, gap: space.lg, paddingBottom: space.xl },
  gap: { gap: space.sm },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  title: { fontSize: 19, fontWeight: '800', color: colors.text, marginTop: space.xs },
  info: { flexDirection: 'row', alignItems: 'center', gap: space.sm },
  infoText: { color: colors.text, fontSize: 14 },
  description: { marginTop: space.sm, fontSize: 15, lineHeight: 24, color: colors.text, backgroundColor: colors.background, padding: space.md, borderRadius: radius.md },
  muted: { color: colors.muted, fontSize: 13 },
  highlight: { borderColor: colors.success, backgroundColor: colors.successSoft },
  highlightTitle: { fontSize: 16, fontWeight: '800', color: colors.success },
  actions: { flexDirection: 'row', gap: space.sm },
  gallery: { flexDirection: 'row', flexWrap: 'wrap', gap: space.sm },
  thumb: { width: 96, height: 96, borderRadius: radius.md, backgroundColor: colors.border },
  file: { flexDirection: 'row', alignItems: 'center', gap: space.sm, minHeight: touch },
  fileName: { flex: 1, color: colors.primary, fontWeight: '600' },
  bubbleWrap: { marginBottom: space.md, maxWidth: '85%' },
  bubbleMine: { alignSelf: 'flex-start' },
  bubbleTheirs: { alignSelf: 'flex-end' },
  author: { fontSize: 12, fontWeight: '700', color: colors.muted, marginBottom: 4 },
  bubble: { padding: space.md, borderRadius: radius.lg },
  bubbleMineBg: { backgroundColor: colors.primarySoft },
  bubbleTheirsBg: { backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.border },
  bubbleText: { fontSize: 15, lineHeight: 22, color: colors.text },
  time: { fontSize: 11, color: colors.muted, marginTop: 4 },
  composer: {
    flexDirection: 'row',
    alignItems: 'flex-end',
    gap: space.sm,
    padding: space.sm,
    backgroundColor: colors.surface,
    borderTopWidth: 1,
    borderTopColor: colors.border,
  },
  iconButton: { width: touch, height: touch, alignItems: 'center', justifyContent: 'center', borderRadius: radius.md, backgroundColor: colors.primarySoft },
  replyInput: {
    flex: 1,
    minHeight: touch,
    maxHeight: 120,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: space.md,
    paddingVertical: space.sm,
    fontSize: 15,
    color: colors.text,
  },
  sendButton: { width: touch, height: touch, borderRadius: radius.md, backgroundColor: colors.primary, alignItems: 'center', justifyContent: 'center' },
  sendDisabled: { opacity: 0.5 },
  sendIcon: { transform: [{ scaleX: -1 }] },
  closed: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: space.sm, padding: space.md, backgroundColor: colors.surface, borderTopWidth: 1, borderTopColor: colors.border },
});
