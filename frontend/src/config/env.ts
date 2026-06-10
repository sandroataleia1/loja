export const env = {
  apiUrl:  process.env.NEXT_PUBLIC_API_URL  ?? 'http://localhost:8000/api/v1',
  appName: process.env.NEXT_PUBLIC_APP_NAME ?? 'Store Admin',
  isDev:   process.env.NODE_ENV === 'development',
  isProd:  process.env.NODE_ENV === 'production',
} as const
