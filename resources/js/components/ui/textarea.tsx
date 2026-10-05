import * as React from 'react'
import { cn } from '@/lib/utils'

/* Nexus textarea: bare like the input. Hairline below, accent on focus, destructive when invalid. */
function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        'border-input text-base md:text-body text-foreground flex field-sizing-content min-h-20 w-full resize-y rounded-none border-0 border-b bg-transparent px-0 py-2 font-medium transition-colors duration-200 outline-none placeholder:text-(--nx-fg-4) disabled:cursor-not-allowed disabled:opacity-50',
        'focus-visible:border-primary',
        'aria-invalid:border-destructive',
        className,
      )}
      {...props}
    />
  )
}

export { Textarea }
