import { GripVertical, Plus, Trash2, ChevronUp, ChevronDown } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import type { EnumOption, EventFormField, FormFieldType } from '@/types'

interface FormBuilderProps {
  fields: EventFormField[]
  onChange: (fields: EventFormField[]) => void
  fieldTypes: EnumOption[]
}

const OPTION_TYPES: FormFieldType[] = ['select', 'checkbox', 'radio']

function emptyField(): EventFormField {
  return {
    label: '',
    type: 'text',
    options: [],
    is_required: false,
    placeholder: '',
    help_text: '',
  }
}

export function FormBuilder({ fields, onChange, fieldTypes }: FormBuilderProps) {
  const update = (index: number, patch: Partial<EventFormField>) => {
    onChange(fields.map((f, i) => (i === index ? { ...f, ...patch } : f)))
  }

  const remove = (index: number) => {
    onChange(fields.filter((_, i) => i !== index))
  }

  const move = (index: number, dir: -1 | 1) => {
    const target = index + dir
    if (target < 0 || target >= fields.length) return
    const next = [...fields]
    ;[next[index], next[target]] = [next[target], next[index]]
    onChange(next)
  }

  const add = () => onChange([...fields, emptyField()])

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <div>
          <h3 className="text-sm font-semibold">Registration Form Fields</h3>
          <p className="text-xs text-muted-foreground">
            Build the custom form attendees fill in when registering.
          </p>
        </div>
        <Button type="button" variant="outline" size="sm" onClick={add}>
          <Plus className="h-4 w-4" />
          Add field
        </Button>
      </div>

      {fields.length === 0 && (
        <p className="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground">
          No custom fields yet. Attendees will only need to confirm their registration.
        </p>
      )}

      {fields.map((field, index) => {
        const needsOptions = OPTION_TYPES.includes(field.type)
        return (
          <Card key={index}>
            <CardContent className="space-y-3 p-4">
              <div className="flex items-start gap-2">
                <GripVertical className="mt-2 h-4 w-4 shrink-0 text-muted-foreground" />
                <div className="grid flex-1 gap-3 sm:grid-cols-2">
                  <div className="space-y-1.5">
                    <Label className="text-xs">Field label</Label>
                    <Input
                      value={field.label}
                      placeholder="e.g. T-Shirt Size"
                      onChange={(e) => update(index, { label: e.target.value })}
                    />
                  </div>
                  <div className="space-y-1.5">
                    <Label className="text-xs">Field type</Label>
                    <Select
                      value={field.type}
                      onValueChange={(v) =>
                        update(index, {
                          type: v as FormFieldType,
                          options: OPTION_TYPES.includes(v as FormFieldType) ? field.options : [],
                        })
                      }
                    >
                      <SelectTrigger>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {fieldTypes.map((t) => (
                          <SelectItem key={t.value} value={t.value}>
                            {t.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
                <div className="flex flex-col gap-1">
                  <button
                    type="button"
                    className="text-muted-foreground hover:text-foreground disabled:opacity-30"
                    onClick={() => move(index, -1)}
                    disabled={index === 0}
                  >
                    <ChevronUp className="h-4 w-4" />
                  </button>
                  <button
                    type="button"
                    className="text-muted-foreground hover:text-foreground disabled:opacity-30"
                    onClick={() => move(index, 1)}
                    disabled={index === fields.length - 1}
                  >
                    <ChevronDown className="h-4 w-4" />
                  </button>
                </div>
              </div>

              {needsOptions && (
                <div className="space-y-1.5 pl-6">
                  <Label className="text-xs">Options (comma separated)</Label>
                  <Input
                    value={field.options.join(', ')}
                    placeholder="Option A, Option B, Option C"
                    onChange={(e) =>
                      update(index, {
                        options: e.target.value
                          .split(',')
                          .map((o) => o.trim())
                          .filter(Boolean),
                      })
                    }
                  />
                </div>
              )}

              <div className="flex flex-wrap items-center justify-between gap-3 pl-6">
                <label className="flex cursor-pointer items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={field.is_required}
                    onChange={(e) => update(index, { is_required: e.target.checked })}
                    className="h-4 w-4 accent-[hsl(var(--primary))]"
                  />
                  Required field
                </label>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="text-destructive hover:text-destructive"
                  onClick={() => remove(index)}
                >
                  <Trash2 className="h-4 w-4" />
                  Remove
                </Button>
              </div>
            </CardContent>
          </Card>
        )
      })}
    </div>
  )
}
