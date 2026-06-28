'use client'

import * as React from 'react'
import { cn } from '@/lib/utils'

interface CheckboxProps {
  checked?: boolean
  onCheckedChange?: (checked: boolean) => void
  disabled?: boolean
  id?: string
  className?: string
}

const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ checked, onCheckedChange, disabled, id, className }, ref) => (
    <input
      ref={ref}
      type="checkbox"
      id={id}
      checked={checked ?? false}
      disabled={disabled}
      onChange={(e) => onCheckedChange?.(e.target.checked)}
      className={cn(
        'h-4 w-4 shrink-0 rounded-sm border border-primary accent-primary cursor-pointer disabled:cursor-not-allowed disabled:opacity-50',
        className,
      )}
    />
  ),
)
Checkbox.displayName = 'Checkbox'

export { Checkbox }
