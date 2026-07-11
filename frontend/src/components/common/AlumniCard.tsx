import { Briefcase, GraduationCap, Mail, MapPin } from 'lucide-react'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { getInitials } from '@/lib/utils'
import type { AlumniProfile } from '@/types'

export function AlumniCard({ profile }: { profile: AlumniProfile }) {
  const name = profile.user?.name ?? 'Alumnus'

  return (
    <Card className="overflow-hidden transition-shadow hover:shadow-md">
      <div className="h-16 bg-gradient-to-r from-primary/80 to-indigo-400" />
      <CardContent className="-mt-8 space-y-3 p-5">
        <Avatar className="h-16 w-16 border-4 border-background">
          <AvatarImage src={profile.profile_photo_url ?? undefined} />
          <AvatarFallback className="text-lg">{getInitials(name)}</AvatarFallback>
        </Avatar>

        <div>
          <h3 className="font-semibold leading-tight">{name}</h3>
          {profile.designation && (
            <p className="text-sm text-muted-foreground">
              {profile.designation}
              {profile.company ? ` · ${profile.company}` : ''}
            </p>
          )}
        </div>

        <div className="flex flex-wrap gap-1.5">
          {profile.batch && (
            <Badge variant="secondary" className="gap-1">
              <GraduationCap className="h-3 w-3" />
              Batch {profile.batch}
            </Badge>
          )}
          {profile.department && <Badge variant="outline">{profile.department}</Badge>}
        </div>

        <div className="space-y-1.5 pt-1 text-sm text-muted-foreground">
          {profile.profession && (
            <p className="flex items-center gap-2">
              <Briefcase className="h-3.5 w-3.5" />
              {profile.profession}
            </p>
          )}
          {profile.address && (
            <p className="flex items-center gap-2">
              <MapPin className="h-3.5 w-3.5" />
              {profile.address}
            </p>
          )}
          {profile.user?.email && (
            <p className="flex items-center gap-2 truncate">
              <Mail className="h-3.5 w-3.5" />
              {profile.user.email}
            </p>
          )}
        </div>
      </CardContent>
    </Card>
  )
}
