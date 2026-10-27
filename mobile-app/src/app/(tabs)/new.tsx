import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { router, useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import {
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Banner, Button, Card, Field } from '@/components/ui';
import { api, ApiError } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { appendFile, pickPhotos, type PickedFile } from '@/lib/images';
import { colors, radius, space, touch } from '@/lib/theme';
import type { Category, Ticket } from '@/lib/types';

const MAX_FILES = 5;

export default function NewTicketScreen() {
  const { user } = useAuth();
  const [categories, setCategories] = useState<Category[]>([]);
  const [category, setCategory] = useState<Category | null>(null);
  const [pickerOpen, setPickerOpen] = useState(false);
  const [title, setTitle] = useState('');
  const [location, setLocation] = useState(user?.office_location ?? '');
  const [description, setDescription] = useState('');
  const [photos, setPhotos] = useState<PickedFile[]>([]);
  const [busyPhotos, setBusyPhotos] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});

  useFocusEffect(
    useCallback(() => {
      api<{ data: Category[] }>('/categories')
        .then((r) => setCategories(r.data))
        .catch((e: ApiError) => setError(e.message));
    }, []),
  );

  const addPhotos = async (source: 'camera' | 'library') => {
    setError(null);
    setBusyPhotos(true);
    try {
      const picked = await pickPhotos(source);
      setPhotos((prev) => [...prev, ...picked].slice(0, MAX_FILES));
    } catch (e) {
      setError((e as Error).message);
    } finally {
      setBusyPhotos(false);
    }
  };

  // clear a field's error as soon as the user edits it
  const edit = (field: string, setter: (v: string) => void) => (value: string) => {
    setter(value);
    if (fieldErrors[field]) setFieldErrors(({ [field]: _removed, ...rest }) => rest);
  };

  const reset = () => {
    setTitle('');
    setDescription('');
    setCategory(null);
    setPhotos([]);
    setFieldErrors({});
  };

  const submit = async () => {
    const errors: Record<string, string> = {};
    if (!category) errors.category_id = 'اختر نوع المشكلة.';
    if (!location.trim()) errors.office_location = 'حدد مكان المشكلة.';
    if (!title.trim()) errors.title = 'اكتب عنواناً للمشكلة.';
    if (!description.trim()) errors.description = 'اشرح المشكلة.';
    setFieldErrors(errors);
    if (Object.keys(errors).length) return;

    setSubmitting(true);
    setError(null);
    try {
      const form = new FormData();
      form.append('title', title.trim());
      form.append('description', description.trim());
      form.append('office_location', location.trim());
      form.append('category_id', String(category!.id));
      for (const photo of photos) {
        await appendFile(form, 'attachments[]', photo);
      }
      const result = await api<{ data: Ticket }>('/tickets', { method: 'POST', form });
      reset();
      router.push({ pathname: '/ticket/[id]', params: { id: String(result.data.id) } });
    } catch (e) {
      const err = e as ApiError;
      const mapped: Record<string, string> = {};
      Object.entries(err.errors ?? {}).forEach(([k, v]) => (mapped[k.split('.')[0]] = v[0]));
      setFieldErrors(mapped);
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
        {error ? <Banner message={error} /> : null}

        <Card>
          <Text style={styles.label}>نوع المشكلة</Text>
          <Pressable
            accessibilityRole="button"
            accessibilityLabel={`نوع المشكلة: ${category?.name ?? 'غير محدد'}`}
            onPress={() => setPickerOpen(true)}
            style={[styles.select, fieldErrors.category_id ? styles.selectError : null]}>
            <Text style={[styles.selectText, !category && styles.placeholder]}>{category?.name ?? 'اختر نوع المشكلة'}</Text>
            <Ionicons name="chevron-down" size={20} color={colors.muted} />
          </Pressable>
          {fieldErrors.category_id ? <Text style={styles.error}>{fieldErrors.category_id}</Text> : null}

          <Field label="مكان المشكلة" value={location} onChangeText={edit('office_location', setLocation)} placeholder="القسم / الغرفة" error={fieldErrors.office_location} />
          <Field label="العنوان" value={title} onChangeText={edit('title', setTitle)} placeholder="مثال: الطابعة لا تطبع" error={fieldErrors.title} maxLength={255} />
          <Field
            label="الوصف"
            value={description}
            onChangeText={edit('description', setDescription)}
            placeholder="اشرح المشكلة بالتفصيل..."
            multiline
            error={fieldErrors.description}
          />

          <Text style={styles.label}>الصور (اختياري، حتى {MAX_FILES})</Text>
          <View style={styles.photoButtons}>
            <Button title="التقاط صورة" icon="camera-outline" variant="secondary" onPress={() => addPhotos('camera')} loading={busyPhotos} disabled={photos.length >= MAX_FILES} style={styles.flex} />
            <Button title="من المعرض" icon="images-outline" variant="secondary" onPress={() => addPhotos('library')} disabled={busyPhotos || photos.length >= MAX_FILES} style={styles.flex} />
          </View>

          {photos.length ? (
            <View style={styles.previews}>
              {photos.map((p, i) => (
                <View key={p.uri} style={styles.preview}>
                  <Image source={{ uri: p.uri }} style={styles.previewImage} contentFit="cover" accessibilityLabel={`صورة ${i + 1}`} />
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel={`حذف الصورة ${i + 1}`}
                    hitSlop={10}
                    onPress={() => setPhotos((prev) => prev.filter((x) => x.uri !== p.uri))}
                    style={styles.remove}>
                    <Ionicons name="close" size={16} color="#FFFFFF" />
                  </Pressable>
                </View>
              ))}
            </View>
          ) : null}
          <Text style={styles.hint}>يتم تصغير الصور تلقائياً قبل الإرسال.</Text>
        </Card>

        <Button title="إرسال التذكرة" icon="send" onPress={submit} loading={submitting} disabled={busyPhotos} />
      </ScrollView>

      <Modal visible={pickerOpen} animationType="slide" transparent onRequestClose={() => setPickerOpen(false)}>
        <Pressable style={styles.backdrop} onPress={() => setPickerOpen(false)} accessibilityLabel="إغلاق" />
        <SafeAreaView edges={['bottom']} style={styles.sheet}>
          <Text style={styles.sheetTitle}>اختر نوع المشكلة</Text>
          <ScrollView>
            {categories.map((c) => (
              <Pressable
                key={c.id}
                accessibilityRole="radio"
                accessibilityState={{ checked: category?.id === c.id }}
                onPress={() => {
                  setCategory(c);
                  if (fieldErrors.category_id) setFieldErrors(({ category_id: _removed, ...rest }) => rest);
                  setPickerOpen(false);
                }}
                style={({ pressed }) => [styles.option, pressed && styles.optionPressed]}>
                <Text style={styles.optionText}>{c.name}</Text>
                {category?.id === c.id ? <Ionicons name="checkmark" size={22} color={colors.primary} /> : null}
              </Pressable>
            ))}
          </ScrollView>
        </SafeAreaView>
      </Modal>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  container: { padding: space.lg, gap: space.lg, paddingBottom: space.xl * 2 },
  label: { fontSize: 15, fontWeight: '700', color: colors.text, marginBottom: space.sm },
  select: {
    minHeight: touch,
    borderWidth: 1,
    borderColor: colors.border,
    borderRadius: radius.md,
    paddingHorizontal: space.md,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: space.lg,
    backgroundColor: colors.surface,
  },
  selectError: { borderColor: colors.danger, marginBottom: space.xs },
  selectText: { fontSize: 16, color: colors.text },
  placeholder: { color: colors.muted },
  error: { color: colors.danger, fontSize: 13, marginBottom: space.lg, writingDirection: 'rtl' },
  photoButtons: { flexDirection: 'row', gap: space.sm },
  previews: { flexDirection: 'row', flexWrap: 'wrap', gap: space.md, marginTop: space.md },
  preview: { width: 84, height: 84 },
  previewImage: { width: 84, height: 84, borderRadius: radius.md, backgroundColor: colors.border },
  remove: {
    position: 'absolute',
    top: -8,
    start: -8,
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: colors.danger,
    alignItems: 'center',
    justifyContent: 'center',
  },
  hint: { color: colors.muted, fontSize: 13, marginTop: space.sm },
  backdrop: { flex: 1, backgroundColor: 'rgba(24,28,50,0.45)' },
  sheet: { backgroundColor: colors.surface, borderTopLeftRadius: radius.lg, borderTopRightRadius: radius.lg, maxHeight: '70%', paddingTop: space.lg },
  sheetTitle: { fontSize: 17, fontWeight: '800', color: colors.text, paddingHorizontal: space.lg, marginBottom: space.sm },
  option: {
    minHeight: touch + 4,
    paddingHorizontal: space.lg,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderBottomColor: colors.border,
  },
  optionPressed: { backgroundColor: colors.background },
  optionText: { fontSize: 16, color: colors.text },
});
