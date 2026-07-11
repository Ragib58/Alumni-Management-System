import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { ArrowLeft } from 'lucide-react'
import { alumniService } from '@/services/alumni.service'
import { useAuth } from '@/hooks/useAuth'
import { useToast } from '@/components/ui/toast'
import { extractApiError } from '@/lib/api'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import { ProfileForm } from '@/components/common/ProfileForm'
import type { AlumniProfile, ProfilePayload } from '@/types'

export function EditProfilePage() {
  const { user, refresh } = useAuth()
  const { toast } = useToast()
  const navigate = useNavigate()
  const [profile, setProfile] = useState<AlumniProfile | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    alumniService
      .myProfile()
      .then(setProfile)
      .catch((e) => toast({ title: 'Failed to load', description: extractApiError(e).message, variant: 'error' }))
      .finally(() => setLoading(false))
  }, [toast])

  const handleSubmit = async (payload: ProfilePayload) => {
    await alumniService.updateMyProfile(payload)
    await refresh()
    toast({ title: 'Profile updated', variant: 'success' })
    navigate('/profile')
  }

  if (loading) {
    return (
      <div className="flex h-64 items-center justify-center">
        <Spinner className="h-8 w-8" />
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-3xl space-y-6">
      <div className="flex items-center gap-3">
        <Button variant="ghost" size="icon" onClick={() => navigate('/profile')}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">Edit Profile</h1>
          <p className="text-muted-foreground">Keep your alumni information up to date.</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Alumni Information</CardTitle>
        </CardHeader>
        <CardContent>
          <ProfileForm
            initial={profile}
            displayName={user?.name ?? 'U'}
            onSubmit={handleSubmit}
          />
        </CardContent>
      </Card>
    </div>
  )
}
