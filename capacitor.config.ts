import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.expensesettle.app',
  appName: 'ExpenseSettle',
  webDir: 'public',
  server: {
    // Point to your production URL
    url: 'https://xpensesettle.on-forge.com',
    androidScheme: 'https',
    cleartext: false, // Production only uses HTTPS
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
