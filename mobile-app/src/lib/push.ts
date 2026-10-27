import Constants from 'expo-constants';
import * as Device from 'expo-device';
import * as Notifications from 'expo-notifications';
import { AndroidImportance } from 'expo-notifications';
import { Platform } from 'react-native';

import { api } from './api';
import { KEYS, storage } from './storage';

/** Must match the channelId the server sends (ExpoPushChannel). */
export const CHANNEL_ID = 'tickets';

export type PushStatus =
  | { state: 'enabled'; token: string }
  | { state: 'denied' }
  | { state: 'unsupported'; reason: string };

// Show notifications while the app is open too.
if (Platform.OS !== 'web') {
  Notifications.setNotificationHandler({
    handleNotification: async () => ({
      shouldShowBanner: true,
      shouldShowList: true,
      shouldPlaySound: true,
      shouldSetBadge: true,
    }),
  });
}

function projectId(): string | undefined {
  return Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;
}

/**
 * Asks for permission, gets this phone's Expo push token and registers it with
 * the server (POST /devices). Never throws: returns why push is unavailable.
 */
export async function registerForPush(): Promise<PushStatus> {
  if (Platform.OS === 'web') {
    return { state: 'unsupported', reason: 'الإشعارات متاحة في تطبيق الجوال فقط.' };
  }
  if (!Device.isDevice) {
    return { state: 'unsupported', reason: 'الإشعارات تعمل على جهاز حقيقي فقط.' };
  }
  if (Constants.executionEnvironment === 'storeClient' && Platform.OS === 'android') {
    return { state: 'unsupported', reason: 'الإشعارات تحتاج نسخة التطبيق المثبتة (لا تعمل داخل Expo Go على أندرويد).' };
  }
  const id = projectId();
  if (!id) {
    return { state: 'unsupported', reason: 'لم يتم ربط التطبيق بمشروع EAS بعد (eas init).' };
  }

  try {
    // Android 13+: the permission prompt only appears once a channel exists
    if (Platform.OS === 'android') {
      await Notifications.setNotificationChannelAsync(CHANNEL_ID, {
        name: 'التذاكر',
        importance: AndroidImportance.HIGH,
        vibrationPattern: [0, 250, 250, 250],
        lightColor: '#0077C2',
      });
    }

    let { status } = await Notifications.getPermissionsAsync();
    if (status !== 'granted') {
      status = (await Notifications.requestPermissionsAsync()).status;
    }
    if (status !== 'granted') {
      return { state: 'denied' };
    }

    const token = (await Notifications.getExpoPushTokenAsync({ projectId: id })).data;
    await api('/devices', {
      method: 'POST',
      body: { token, platform: Platform.OS, device_name: Device.modelName ?? undefined },
    });
    await storage.set(KEYS.pushToken, token);

    return { state: 'enabled', token };
  } catch {
    return { state: 'unsupported', reason: 'تعذر تفعيل الإشعارات الآن، سيتم المحاولة عند فتح التطبيق مرة أخرى.' };
  }
}

/** Removes this phone from the server before signing out. */
export async function unregisterPush(): Promise<void> {
  const token = await storage.get(KEYS.pushToken);
  if (!token) return;
  try {
    await api('/devices', { method: 'DELETE', body: { token } });
  } catch {
    // logout also deletes devices registered with this session's token
  }
  await storage.remove(KEYS.pushToken);
}

/** Ticket id carried by a notification (server sends data.ticket_id). */
export function ticketIdFrom(response: Notifications.NotificationResponse | null): number | null {
  const raw = response?.notification.request.content.data?.ticket_id;
  const id = typeof raw === 'number' ? raw : Number(raw);
  return Number.isFinite(id) && id > 0 ? id : null;
}
