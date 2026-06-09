import type { NextConfig } from 'next'

const config: NextConfig = {
  transpilePackages: ['@store/shared-types', '@store/contracts'],
  images: {
    unoptimized: true,
  },
}

export default config
