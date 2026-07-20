import { useEffect, useMemo, useState } from 'react'
import { Save } from 'lucide-react'
import { settingsService } from '@/services/settings.service'
import { useSettings } from '@/context/SettingsContext'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { cn } from '@/lib/utils'
import type { GroupedSettings } from '@/types'

const GROUP_LABELS: Record<string, string> = {
  site: 'Site',
  theme: 'Theme',
  payment: 'Payment',
  email: 'Email',
  sms: 'SMS',
}

const GROUP_ORDER = ['site', 'theme', 'payment', 'email', 'sms']

function humanize(key: string): string {
  const last = key.split('.').slice(1).join(' ')
  return last.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

export function SettingsPage() {
  const { toast } = useToast()
  const { refresh } = useSettings()
  const [grouped, setGrouped] = useState<GroupedSettings>({})
  const [values, setValues] = useState<Record<string, string>>({})
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [tab, setTab] = useState('site')

  useEffect(() => {
    settingsService
      .all()
      .then((data) => {
        setGrouped(data)
        const initial: Record<string, string> = {}
        Object.values(data).flat().forEach((s) => {
          initial[s.key] = s.value === null || s.value === undefined ? '' : String(s.value)
        })
        setValues(initial)
      })
      .catch((e) => toast({ title: 'Failed to load settings', description: extractApiError(e).message, variant: 'error' }))
      .finally(() => setLoading(false))
  }, [toast])

  const tabs = useMemo(
    () => GROUP_ORDER.filter((g) => grouped[g]?.length),
    [grouped],
  )

  const save = async () => {
    setSaving(true)
    try {
      // Only submit the current tab's editable values.
      const items = (grouped[tab] ?? [])
        .filter((s) => {
          // Skip secrets left as the masked placeholder.
          const v = values[s.key]
          return !(s.is_encrypted && (v === '••••••••' || v === ''))
        })
        .map((s) => ({ key: s.key, value: values[s.key] === '' ? null : values[s.key] }))

      if (items.length > 0) {
        await settingsService.update(items)
      }
      await refresh()
      toast({ title: 'Settings saved', variant: 'success' })
    } catch (e) {
      toast({ title: 'Save failed', description: extractApiError(e).message, variant: 'error' })
    } finally {
      setSaving(false)
    }
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  const current = grouped[tab] ?? []

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold">Settings</h1>
        <p className="text-muted-foreground">Manage site, payment, email, SMS and theme configuration.</p>
      </div>

      <div className="flex gap-1 border-b">
        {tabs.map((g) => (
          <button
            key={g}
            onClick={() => setTab(g)}
            className={cn(
              'border-b-2 px-4 py-2 text-sm font-medium transition-colors',
              tab === g ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground',
            )}
          >
            {GROUP_LABELS[g] ?? g}
          </button>
        ))}
      </div>

      <Card>
        <CardContent className="space-y-4 p-6">
          {current.map((s) => (
            <div key={s.key} className="grid gap-2 sm:grid-cols-3 sm:items-center">
              <Label className="sm:col-span-1">
                {humanize(s.key)}
                {s.is_encrypted && <span className="ml-1 text-xs text-muted-foreground">(secret)</span>}
              </Label>
              <div className="sm:col-span-2">
                {tab === 'theme' && s.key === 'theme.mode' ? (
                  <select
                    value={values[s.key] ?? 'light'}
                    onChange={(e) => setValues((v) => ({ ...v, [s.key]: e.target.value }))}
                    className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm"
                  >
                    <option value="light">Light</option>
                    <option value="dark">Dark</option>
                  </select>
                ) : (
                  <Input
                    type={s.key.includes('color') ? 'text' : s.is_encrypted ? 'password' : 'text'}
                    value={values[s.key] ?? ''}
                    placeholder={s.is_encrypted ? '••••••••' : ''}
                    onChange={(e) => setValues((v) => ({ ...v, [s.key]: e.target.value }))}
                  />
                )}
              </div>
            </div>
          ))}

          <div className="flex justify-end border-t pt-4">
            <Button onClick={save} disabled={saving}>
              {saving ? <Spinner className="h-4 w-4 text-primary-foreground" /> : <Save className="h-4 w-4" />}
              Save {GROUP_LABELS[tab]} settings
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}
