import Ionicons from '@expo/vector-icons/Ionicons';
import { Image } from 'expo-image';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { Banner, Button, Card, Field } from '@/components/ui';
import { ApiError, getServerUrl } from '@/lib/api';
import { useAuth } from '@/lib/auth';
import { colors, space } from '@/lib/theme';

export default function LoginScreen() {
  const { signIn } = useAuth();
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [server, setServer] = useState(getServerUrl());
  const [showServer, setShowServer] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [offline, setOffline] = useState(false);

  const submit = async () => {
    if (!username.trim() || !password) {
      setError('يرجى إدخال اسم المستخدم وكلمة المرور.');
      return;
    }
    setLoading(true);
    setError(null);
    setOffline(false);
    try {
      await signIn(username, password, server);
    } catch (e) {
      const err = e as ApiError;
      setOffline(!!err.offline);
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.container} keyboardShouldPersistTaps="handled">
          <View style={styles.header}>
            <Image source={require('@/assets/images/icon.png')} style={styles.logo} contentFit="contain" />
            <Text style={styles.title}>نظام الدعم الفني</Text>
            <Text style={styles.subtitle}>سجّل الدخول بحساب الشبكة الخاص بك</Text>
          </View>

          <Card>
            {error ? <Banner message={error} icon={offline ? 'wifi-outline' : undefined} /> : null}

            <Field
              label="اسم المستخدم"
              value={username}
              onChangeText={setUsername}
              autoCapitalize="none"
              autoCorrect={false}
              autoComplete="username"
              textContentType="username"
              returnKeyType="next"
              placeholder="مثال: a.ahmad"
            />
            <Field
              label="كلمة المرور"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoComplete="password"
              textContentType="password"
              returnKeyType="go"
              onSubmitEditing={submit}
            />

            <Button title="دخول" icon="log-in-outline" onPress={submit} loading={loading} />
          </Card>

          <Pressable
            accessibilityRole="button"
            accessibilityState={{ expanded: showServer }}
            onPress={() => setShowServer((v) => !v)}
            style={styles.serverToggle}>
            <Ionicons name="server-outline" size={18} color={colors.muted} />
            <Text style={styles.serverToggleText}>إعدادات الخادم</Text>
            <Ionicons name={showServer ? 'chevron-up' : 'chevron-down'} size={18} color={colors.muted} />
          </Pressable>

          {showServer ? (
            <Card>
              <Field
                label="عنوان الخادم"
                value={server}
                onChangeText={setServer}
                autoCapitalize="none"
                autoCorrect={false}
                keyboardType="url"
                placeholder="http://172.16.10.105:9091"
              />
              <Text style={styles.hint}>يعمل التطبيق فقط عند الاتصال بشبكة Wi-Fi المستشفى.</Text>
            </Card>
          ) : null}
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  flex: { flex: 1 },
  container: { padding: space.xl, gap: space.lg, flexGrow: 1, justifyContent: 'center', maxWidth: 480, width: '100%', alignSelf: 'center' },
  header: { alignItems: 'center', gap: space.sm, marginBottom: space.sm },
  logo: { width: 88, height: 88 },
  title: { fontSize: 24, fontWeight: '800', color: colors.text },
  subtitle: { fontSize: 15, color: colors.muted },
  serverToggle: { flexDirection: 'row', alignItems: 'center', justifyContent: 'center', gap: space.sm, minHeight: 48 },
  serverToggleText: { color: colors.muted, fontWeight: '600' },
  hint: { color: colors.muted, fontSize: 13 },
});
