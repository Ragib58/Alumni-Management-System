import { useRef, useState } from 'react'
import { useForm } from 'react-hook-form'
import { Upload } from 'lucide-react'
import { extractApiError } from '@/lib/api'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Spinner } from '@/components/ui/spinner'
import { getInitials } from '@/lib/utils'
import type { AlumniProfile, ProfilePayload } from '@/types'

interface Props {
  initial: AlumniProfile | null
  displayName: string
  onSubmit: (payload: ProfilePayload) => Promise<void>
  submitLabel?: string
}

interface FormValues {
  student_id: string
  batch: string
  department: string
  session: string
  profession: string
  company: string
  designation: string
  address: string
  bio: string
}

export function ProfileForm({ initial, displayName, onSubmit, submitLabel = 'Save changes' }: Props) {
  const fileRef = useRef<HTMLInputElement>(null)
  const [photo, setPhoto] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(initial?.profile_photo_url ?? null)
  const [serverError, setServerError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    defaultValues: {
      student_id: initial?.student_id ?? '',
      batch: initial?.batch ?? '',
      department: initial?.department ?? '',
      session: initial?.session ?? '',
      profession: initial?.profession ?? '',
      company: initial?.company ?? '',
      designation: initial?.designation ?? '',
      address: initial?.address ?? '',
      bio: initial?.bio ?? '',
    },
  })

  const handleFile = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setPhoto(file)
      setPreview(URL.createObjectURL(file))
    }
  }

  const submit = async (values: FormValues) => {
    setServerError(null)
    try {
      await onSubmit({ ...values, profile_photo: photo })
    } catch (e) {
      const err = extractApiError(e)
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, messages]) => {
          setError(field as keyof FormValues, { message: messages[0] })
        })
      }
      setServerError(err.message)
    }
  }

  return (
    <form onSubmit={handleSubmit(submit)} className="space-y-6" noValidate>
      {serverError && (
        <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
          {serverError}
        </div>
      )}

      {/* Avatar */}
      <div className="flex items-center gap-4">
        <Avatar className="h-20 w-20">
          <AvatarImage src={preview ?? undefined} />
          <AvatarFallback className="text-xl">{getInitials(displayName)}</AvatarFallback>
        </Avatar>
        <div>
          <input
            ref={fileRef}
            type="file"
            accept="image/png,image/jpeg,image/webp"
            className="hidden"
            onChange={handleFile}
          />
          <Button type="button" variant="outline" size="sm" onClick={() => fileRef.current?.click()}>
            <Upload className="h-4 w-4" />
            Upload photo
          </Button>
          <p className="mt-1 text-xs text-muted-foreground">JPG, PNG or WebP. Max 2MB.</p>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        <Field label="Student ID" error={errors.student_id?.message}>
          <Input {...register('student_id')} />
        </Field>
        <Field label="Batch" error={errors.batch?.message}>
          <Input placeholder="2015" {...register('batch')} />
        </Field>
        <Field label="Department" error={errors.department?.message}>
          <Input placeholder="CSE" {...register('department')} />
        </Field>
        <Field label="Session" error={errors.session?.message}>
          <Input placeholder="2015-2016" {...register('session')} />
        </Field>
        <Field label="Profession" error={errors.profession?.message}>
          <Input placeholder="Software Engineer" {...register('profession')} />
        </Field>
        <Field label="Company" error={errors.company?.message}>
          <Input {...register('company')} />
        </Field>
        <Field label="Designation" error={errors.designation?.message}>
          <Input {...register('designation')} />
        </Field>
        <Field label="Address" error={errors.address?.message}>
          <Input {...register('address')} />
        </Field>
      </div>

      <Field label="Bio" error={errors.bio?.message}>
        <Textarea rows={4} placeholder="Tell us about yourself…" {...register('bio')} />
      </Field>

      <div className="flex justify-end">
        <Button type="submit" disabled={isSubmitting}>
          {isSubmitting && <Spinner className="h-4 w-4 text-primary-foreground" />}
          {submitLabel}
        </Button>
      </div>
    </form>
  )
}

function Field({
  label,
  error,
  children,
}: {
  label: string
  error?: string
  children: React.ReactNode
}) {
  return (
    <div className="space-y-2">
      <Label>{label}</Label>
      {children}
      {error && <p className="text-xs text-destructive">{error}</p>}
    </div>
  )
}
