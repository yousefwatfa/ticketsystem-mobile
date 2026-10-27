import Ionicons from '@expo/vector-icons/Ionicons';
import { Tabs } from 'expo-router';

import { NotificationBell } from '@/components/notification-bell';
import { useAuth } from '@/lib/auth';
import { colors } from '@/lib/theme';

export default function TabsLayout() {
  const { user } = useAuth();

  return (
    <Tabs
      screenOptions={{
        tabBarActiveTintColor: colors.primary,
        tabBarInactiveTintColor: colors.muted,
        tabBarLabelStyle: { fontSize: 12, fontWeight: '700' },
        tabBarStyle: { minHeight: 60 },
        headerStyle: { backgroundColor: colors.surface },
        headerTitleStyle: { fontWeight: '800', color: colors.text },
        sceneStyle: { backgroundColor: colors.background },
        headerRight: () => <NotificationBell />,
      }}>
      <Tabs.Screen
        name="index"
        options={{
          title: 'تذاكري',
          tabBarLabel: 'الرئيسية',
          tabBarIcon: ({ color, focused }) => <Ionicons name={focused ? 'home' : 'home-outline'} size={24} color={color as string} />,
        }}
      />
      <Tabs.Screen
        name="admin"
        options={{
          title: 'تذاكر القسم',
          tabBarLabel: 'التذاكر',
          href: user?.is_admin ? undefined : null,
          tabBarIcon: ({ color, focused }) => <Ionicons name={focused ? 'file-tray-full' : 'file-tray-full-outline'} size={24} color={color as string} />,
        }}
      />
      <Tabs.Screen
        name="new"
        options={{
          title: 'تذكرة جديدة',
          tabBarIcon: ({ color, focused }) => <Ionicons name={focused ? 'add-circle' : 'add-circle-outline'} size={28} color={color as string} />,
        }}
      />
      <Tabs.Screen
        name="account"
        options={{
          title: 'حسابي',
          tabBarIcon: ({ color, focused }) => <Ionicons name={focused ? 'person' : 'person-outline'} size={24} color={color as string} />,
        }}
      />
    </Tabs>
  );
}
