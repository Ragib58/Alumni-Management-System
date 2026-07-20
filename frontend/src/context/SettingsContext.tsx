import { createContext, useContext, useEffect, useState, type ReactNode } from 'react'
import { settingsService } from '@/services/settings.service'
import type { PublicSettings } from '@/types'

interface SettingsContextValue {
  settings: PublicSettings
  siteName: string
  loading: boolean
  refresh: () => Promise<void>
}

const SettingsContext = createContext<SettingsContextValue | undefined>(undefined)

const DEFAULT_NAME = import.meta.env.VITE_APP_NAME ?? 'Alumni Event Management'

export function SettingsProvider({ children }: { children: ReactNode }) {
  const [settings, setSettings] = useState<PublicSettings>({})
  const [loading, setLoading] = useState(true)

  const applyTheme = (data: PublicSettings) => {
    const primary = data['theme.primary_color']
    if (primary) {
      // Convert hex → HSL is non-trivial; expose raw hex as a fallback var and
      // let CSS use it where needed. The design tokens remain the default.
      document.documentElement.style.setProperty('--brand-color', primary)
    }
    const name = data['site.name'] ?? DEFAULT_NAME
    document.title = name

    const favicon = data['site.favicon']
    if (favicon) {
      let link = document.querySelector<HTMLLinkElement>("link[rel~='icon']")
      if (!link) {
        link = document.createElement('link')
        link.rel = 'icon'
        document.head.appendChild(link)
      }
      link.href = favicon
    }
  }

  const load = async () => {
    try {
      const data = await settingsService.publicSettings()
      setSettings(data)
      applyTheme(data)
    } catch {
      // Public settings are best-effort; fall back to defaults.
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void load()
  }, [])

  const value: SettingsContextValue = {
    settings,
    siteName: (settings['site.name'] as string) ?? DEFAULT_NAME,
    loading,
    refresh: load,
  }

  return <SettingsContext.Provider value={value}>{children}</SettingsContext.Provider>
}

// eslint-disable-next-line react-refresh/only-export-components
export function useSettings() {
  const ctx = useContext(SettingsContext)
  if (!ctx) throw new Error('useSettings must be used within <SettingsProvider>')
  return ctx
}
