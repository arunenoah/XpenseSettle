import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.expensesettle.app',
  appName: 'ExpenseSettle',
  webDir: 'public',
  server: {
    androidScheme: 'https',
    cleartext: true, // Allow local development
  },
  ios: {
    scheme: 'ExpenseSettle',
  },
  android: {
    buildOptions: {
      keystorePath: '~/.keystore/expensesettle.keystore',
      keystorePassword: process.env.KEYSTORE_PASSWORD,
      keystoreAlias: 'expensesettle',
      keystoreAliasPassword: process.env.KEYSTORE_ALIAS_PASSWORD,
      releaseType: 'APK',
    },
  },
  plugins: {
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#ffffff',
      androidScaleType: 'center',
      showSpinner: false,
    },
    Camera: {
      permissions: ['camera', 'photos'],
    },
    Geolocation: {
      permissions: ['location'],
    },
  },
};

export default config;
