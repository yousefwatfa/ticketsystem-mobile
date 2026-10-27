import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect } from 'react';
import { AppState, Platform } from 'react-native';

import { ticketIdFrom } from './push';
import { unread } from './unread';

/**
 * Opens the ticket when the user taps a notification (also when the tap
 * launched the app), and keeps the unread badge fresh while signed in.
 */
export function useNotificationTaps(signedIn: boolean) {
  useEffect(() => {
    if (!signedIn || Platform.OS === 'web') return;

    const open = (response: Notifications.NotificationResponse | null) => {
      if (!response || response.actionIdentifier !== Notifications.DEFAULT_ACTION_IDENTIFIER) return;
      const id = ticketIdFrom(response);
      Notifications.clearLastNotificationResponse();
      if (id) router.push({ pathname: '/ticket/[id]', params: { id: String(id) } });
    };

    // cold start: the tap that opened the app (kept until the user has signed in)
    open(Notifications.getLastNotificationResponse());

    const tapped = Notifications.addNotificationResponseReceivedListener(open);
    const received = Notifications.addNotificationReceivedListener(() => void unread.refresh());

    return () => {
      tapped.remove();
      received.remove();
    };
  }, [signedIn]);

  useEffect(() => {
    if (!signedIn) return;
    void unread.refresh();
    const sub = AppState.addEventListener('change', (state) => {
      if (state === 'active') void unread.refresh();
    });
    return () => sub.remove();
  }, [signedIn]);
}
