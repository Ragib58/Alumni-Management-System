import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { userService } from '@/services/user.service'
import { extractApiError } from '@/lib/api'
import { useToast } from '@/components/ui/toast'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { RoleName, User, UserStatus } from '@/types'

const ROLES: { value: RoleName; label: string }[] = [
  { value: 'super_admin', label: 'Super Admin' },
  { value: 'event_manager', label: 'Event Manager' },
  { value: 'alumni_member', label: 'Alumni Member' },
  { value: 'guest', label: 'Guest' },
]

const STATUSES: { value: UserStatus; label: string }[] = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
  { value: 'suspended', label: 'Suspended' },
]

interface Props {
  open: boolean
  onOpenChange: (open: boolean) => void
  user: User | null // null => create mode
  onSaved: () => void
  canCreate: boolean
}

interface FormValues {
  name: string
  email: string
  phone: string
  password: string
  password_confirmation: string
}

export function UserFormDialog({ open, onOpenChange, user, onSaved, canCreate }: Props) {
  const editing = !!user
  const { toast } = useToast()
  const [role, setRole] = useState<RoleName>('alumni_member')
  const [status, setStatus] = useState<UserStatus>('active')
  const [serverError, setServerError] = useState<string | null>(null)

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>()

  useEffect(() => {
    if (open) {
      setServerError(null)
      reset({
        name: user?.name ?? '',
        email: user?.email ?? '',
        phone: user?.phone ?? '',
        password: '',
        password_confirmation: '',
      })
      setRole((user?.roles?.[0] as RoleName) ?? 'alumni_member')
      setStatus(user?.status ?? 'active')
    }
  }, [open, user, reset])

  const onSubmit = async (values: FormValues) => {
    setServerError(null)
    try {
      if (editing && user) {
        await userService.update(user.id, {
          name: values.name,
          email: values.email,
          phone: values.phone || undefined,
          password: values.password || undefined,
          password_confirmation: values.password_confirmation || undefined,
          status,
          roles: [role],
        })
        toast({ title: 'User updated', variant: 'success' })
      } else {
        await userService.create({
          name: values.name,
          email: values.email,
          phone: values.phone || undefined,
          password: values.password,
          password_confirmation: values.password_confirmation,
          status,
          roles: [role],
        })
        toast({ title: 'User created', variant: 'success' })
      }
      onSaved()
      onOpenChange(false)
    } catch (e) {
      const err = extractApiError(e)
      const formFields = ['name', 'email', 'phone', 'password', 'password_confirmation']
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, messages]) => {
          if (formFields.includes(field)) {
            setError(field as keyof FormValues, { message: messages[0] })
          }
        })
      }
      setServerError(err.message)
    }
  }

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{editing ? 'Edit user' : 'Create user'}</DialogTitle>
          <DialogDescription>
            {editing ? 'Update the account details and role.' : 'Add a new account to the system.'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4" noValidate>
          {serverError && (
            <div className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
              {serverError}
            </div>
          )}
          <div className="space-y-2">
            <Label htmlFor="name">Full name</Label>
            <Input id="name" {...register('name', { required: 'Name is required' })} />
            {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
          </div>
          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" {...register('email', { required: 'Email is required' })} />
              {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
            </div>
            <div className="space-y-2">
              <Label htmlFor="phone">Phone</Label>
              <Input id="phone" {...register('phone')} />
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label>Role</Label>
              <Select value={role} onValueChange={(v) => setRole(v as RoleName)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {ROLES.map((r) => (
                    <SelectItem key={r.value} value={r.value}>
                      {r.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="space-y-2">
              <Label>Status</Label>
              <Select value={status} onValueChange={(v) => setStatus(v as UserStatus)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {STATUSES.map((s) => (
                    <SelectItem key={s.value} value={s.value}>
                      {s.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="password">
                {editing ? 'New password (optional)' : 'Password'}
              </Label>
              <Input
                id="password"
                type="password"
                {...register('password', {
                  required: editing ? false : 'Password is required',
                  minLength: { value: 8, message: 'At least 8 characters' },
                })}
              />
              {errors.password && (
                <p className="text-xs text-destructive">{errors.password.message}</p>
              )}
            </div>
            <div className="space-y-2">
              <Label htmlFor="password_confirmation">Confirm password</Label>
              <Input id="password_confirmation" type="password" {...register('password_confirmation')} />
            </div>
          </div>

          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || (!editing && !canCreate)}>
              {isSubmitting && <Spinner className="h-4 w-4 text-primary-foreground" />}
              {editing ? 'Save changes' : 'Create user'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
