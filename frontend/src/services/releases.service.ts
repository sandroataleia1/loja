const REPO = 'sandroataleia1/loja'

export interface ReleaseAsset {
  name:                 string
  browser_download_url: string
  size:                 number
}

export interface Release {
  tag_name:     string
  name:         string
  published_at: string
  html_url:     string
  assets:       ReleaseAsset[]
}

export const releasesService = {
  async getLatest(): Promise<Release | null> {
    try {
      const res = await fetch(
        `https://api.github.com/repos/${REPO}/releases/latest`,
        { headers: { Accept: 'application/vnd.github+json' }, next: { revalidate: 3600 } }
      )
      if (!res.ok) return null
      return res.json()
    } catch {
      return null
    }
  },
}
