import { Upload } from 'lucide-react'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { EventFormField } from '@/types'

export type FieldValue = string | string[]

interface DynamicFormRendererProps {
  fields: EventFormField[]
  values: Record<string, FieldValue>
  onChange: (name: string, value: FieldValue) => void
  files: Record<string, File>
  onFileChange: (name: string, file: File | null) => void
  /** Server-side validation errors keyed by `form_response.<name>`. */
  errors?: Record<string, string[]>
}

export function DynamicFormRenderer({
  fields,
  values,
  onChange,
  files,
  onFileChange,
  errors = {},
}: DynamicFormRendererProps) {
  if (fields.length === 0) {
    return (
      <p className="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground">
        No additional information is required. Just confirm your registration below.
      </p>
    )
  }

  return (
    <div className="space-y-5">
      {fields.map((field) => {
        const name = field.name ?? ''
        const error = errors[`form_response.${name}`]?.[0]
        const value = values[name]

        return (
          <div key={name} className="space-y-2">
            <Label>
              {field.label}
              {field.is_required && <span className="ml-0.5 text-destructive">*</span>}
            </Label>

            {renderField(field, name, value, onChange, files, onFileChange)}

            {field.help_text && (
              <p className="text-xs text-muted-foreground">{field.help_text}</p>
            )}
            {error && <p className="text-xs text-destructive">{error}</p>}
          </div>
        )
      })}
    </div>
  )
}

function renderField(
  field: EventFormField,
  name: string,
  value: FieldValue | undefined,
  onChange: (name: string, value: FieldValue) => void,
  files: Record<string, File>,
  onFileChange: (name: string, file: File | null) => void,
) {
  const strValue = typeof value === 'string' ? value : ''
  const arrValue = Array.isArray(value) ? value : []

  switch (field.type) {
    case 'textarea':
      return (
        <Textarea
          rows={3}
          placeholder={field.placeholder ?? ''}
          value={strValue}
          onChange={(e) => onChange(name, e.target.value)}
        />
      )

    case 'select':
      return (
        <Select value={strValue} onValueChange={(v) => onChange(name, v)}>
          <SelectTrigger>
            <SelectValue placeholder={field.placeholder ?? 'Select an option'} />
          </SelectTrigger>
          <SelectContent>
            {field.options.map((opt) => (
              <SelectItem key={opt} value={opt}>
                {opt}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      )

    case 'radio':
      return (
        <div className="space-y-1.5">
          {field.options.map((opt) => (
            <label key={opt} className="flex cursor-pointer items-center gap-2 text-sm">
              <input
                type="radio"
                name={name}
                value={opt}
                checked={strValue === opt}
                onChange={() => onChange(name, opt)}
                className="h-4 w-4 accent-[hsl(var(--primary))]"
              />
              {opt}
            </label>
          ))}
        </div>
      )

    case 'checkbox':
      return (
        <div className="space-y-1.5">
          {field.options.map((opt) => (
            <label key={opt} className="flex cursor-pointer items-center gap-2 text-sm">
              <input
                type="checkbox"
                value={opt}
                checked={arrValue.includes(opt)}
                onChange={(e) => {
                  const next = e.target.checked
                    ? [...arrValue, opt]
                    : arrValue.filter((v) => v !== opt)
                  onChange(name, next)
                }}
                className="h-4 w-4 accent-[hsl(var(--primary))]"
              />
              {opt}
            </label>
          ))}
        </div>
      )

    case 'file':
      return (
        <div className="flex items-center gap-3">
          <label className="inline-flex cursor-pointer items-center gap-2 rounded-md border px-3 py-2 text-sm hover:bg-accent">
            <Upload className="h-4 w-4" />
            {files[name] ? 'Change file' : 'Choose file'}
            <input
              type="file"
              className="hidden"
              onChange={(e) => onFileChange(name, e.target.files?.[0] ?? null)}
            />
          </label>
          {files[name] && (
            <span className="truncate text-sm text-muted-foreground">{files[name].name}</span>
          )}
        </div>
      )

    case 'number':
      return (
        <Input
          type="number"
          placeholder={field.placeholder ?? ''}
          value={strValue}
          onChange={(e) => onChange(name, e.target.value)}
        />
      )

    case 'email':
      return (
        <Input
          type="email"
          placeholder={field.placeholder ?? ''}
          value={strValue}
          onChange={(e) => onChange(name, e.target.value)}
        />
      )

    case 'text':
    default:
      return (
        <Input
          type="text"
          placeholder={field.placeholder ?? ''}
          value={strValue}
          onChange={(e) => onChange(name, e.target.value)}
        />
      )
  }
}
