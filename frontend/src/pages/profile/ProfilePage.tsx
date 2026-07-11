import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import {
  Briefcase,
  Building2,
  GraduationCap,
  Mail,
  MapPin,
  Pencil,
  Phone,
  IdCard,
} from 'lucide-react'
import { alumniService } from '@/services/alumni.service'
import { useAuth } from '@/hooks/useAuth'
import { extractApiError } from '@/lib/api'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { StatusBadge } from '@/components/common/StatusBadge'
import { getInitials } from '@/lib/utils'
import type { AlumniProfile } from '@/types'

export function ProfilePage() {
  const { user } = useAuth()
  const [profile, setProfile] = useState<AlumniProfile | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    alumniService
      .myProfile()
      .then(setProfile)
      .catch((e) => setError(extractApiError(e).message))
      .finally(() => setLoading(false))
  }, [])

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  if (error) return <p className="text-destructive">{error}</p>

  const rows = [
    { icon: IdCard, label: 'Student ID', value: profile?.student_id },
    { icon: GraduationCap, label: 'Batch', value: profile?.batch },
    { icon: GraduationCap, label: 'Department', value: profile?.department },
    { icon: GraduationCap, label: 'Session', value: profile?.session },
    { icon: Briefcase, label: 'Profession', value: profile?.profession },
    { icon: Building2, label: 'Company', value: profile?.company },
    { icon: Briefcase, label: 'Designation', value: profile?.designation },
    { icon: MapPin, label: 'Address', value: profile?.address },
    { icon: Phone, label: 'Phone', value: user?.phone },
    { icon: Mail, label: 'Email', value: user?.email },
  ]

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">My Profile</h1>
        <Link to="/profile/edit">
          <Button>
            <Pencil className="h-4 w-4" />
            Edit profile
          </Button>
        </Link>
      </div>

      <Card>
        <div className="h-24 rounded-t-xl bg-gradient-to-r from-primary to-indigo-500" />
        <CardContent className="-mt-12 space-y-4 p-6">
          <div className="flex flex-col gap-4 sm:flex-row sm:items-end">
            <Avatar className="h-24 w-24 border-4 border-background">
              <AvatarImage src={profile?.profile_photo_url ?? undefined} />
              <AvatarFallback className="text-2xl">
                {getInitials(user?.name ?? 'U')}
              </AvatarFallback>
            </Avatar>
            <div className="pb-1">
              <div className="flex items-center gap-2">
                <h2 className="text-xl font-bold">{user?.name}</h2>
                {user && <StatusBadge status={user.status} />}
              </div>
              <p className="text-muted-foreground">
                {profile?.designation ?? 'Alumni Member'}
                {profile?.company ? ` at ${profile.company}` : ''}
              </p>
              <div className="mt-1 flex flex-wrap gap-1.5">
                {user?.roles.map((r) => (
                  <Badge key={r} variant="secondary">
                    {r.replace('_', ' ')}
                  </Badge>
                ))}
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Details</CardTitle>
          </CardHeader>
          <CardContent>
            <dl className="grid gap-4 sm:grid-cols-2">
              {rows.map(({ icon: Icon, label, value }) => (
                <div key={label} className="flex items-start gap-3">
                  <Icon className="mt-0.5 h-4 w-4 text-muted-foreground" />
                  <div>
                    <dt className="text-xs text-muted-foreground">{label}</dt>
                    <dd className="text-sm font-medium">{value || '—'}</dd>
                  </div>
                </div>
              ))}
            </dl>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>About</CardTitle>
          </CardHeader>
          <CardContent>
            <p className="whitespace-pre-line text-sm text-muted-foreground">
              {profile?.bio || 'No bio added yet.'}
            </p>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
