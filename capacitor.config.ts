import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.hrconnect.app',
  appName: 'HRConnect',
  webDir: 'public',
  server: {
    androidScheme: 'https',
  },
  plugins: {
    StatusBar: {
      style: 'default',
      backgroundColor: '#0a0a0a',
    },
    SplashScreen: {
      launchShowDuration: 2000,
      backgroundColor: '#0a0a0a',
    },
    CapacitorHttp: {
      enabled: true,
    },
  },
};

export default config;
