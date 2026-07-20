import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils'
import type { Sponsor, SponsorType } from '@/types'

const TIER_STYLE: Record<SponsorType, string> = {
  platinum: 'ring-slate-300',
  gold: 'ring-amber-300',
  silver: 'ring-zinc-300',
  bronze: 'ring-orange-300',
}

const TIER_LABEL: Record<SponsorType, string> = {
  platinum: 'Platinum',
  gold: 'Gold',
  silver: 'Silver',
  bronze: 'Bronze',
}

const ORDER: SponsorType[] = ['platinum', 'gold', 'silver', 'bronze']

/**
 * Displays an event's sponsors grouped by tier (used on the event page).
 */
export function SponsorList({ sponsors }: { sponsors: Sponsor[] }) {
  if (!sponsors || sponsors.length === 0) return null

  const byTier = ORDER.map((tier) => ({
    tier,
    items: sponsors.filter((s) => s.sponsor_type === tier),
  })).filter((g) => g.items.length > 0)

  return (
    <Card>
      <CardHeader>
        <CardTitle className="text-base">Sponsors</CardTitle>
      </CardHeader>
      <CardContent className="space-y-5">
        {byTier.map(({ tier, items }) => (
          <div key={tier}>
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
              {TIER_LABEL[tier]}
            </p>
            <div className="flex flex-wrap gap-3">
              {items.map((s) => (
                <SponsorBadge key={s.id} sponsor={s} className={TIER_STYLE[tier]} />
              ))}
            </div>
          </div>
        ))}
      </CardContent>
    </Card>
  )
}

function SponsorBadge({ sponsor, className }: { sponsor: Sponsor; className?: string }) {
  const inner = (
    <div
      className={cn(
        'flex h-16 w-32 items-center justify-center overflow-hidden rounded-lg bg-background p-2 ring-1',
        className,
      )}
    >
      {sponsor.logo_url ? (
        <img src={sponsor.logo_url} alt={sponsor.name} className="max-h-full max-w-full object-contain" />
      ) : (
        <span className="text-center text-sm font-medium">{sponsor.name}</span>
      )}
    </div>
  )

  return sponsor.website ? (
    <a href={sponsor.website} target="_blank" rel="noopener noreferrer" title={sponsor.name}>
      {inner}
    </a>
  ) : (
    inner
  )
}
