import * as React from 'react'
import { cn } from '@/lib/utils'

/* Nexus input: bare. No box, no fill — the label above and the hairline below do the framing.
   Focus turns the hairline to the accent; aria-invalid turns it to the destructive colour. */
function Input({ className, type, ...props }: React.ComponentProps<'input'>) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        'border-input text-base md:text-body text-foreground selection:bg-primary selection:text-primary-foreground file:text-foreground h-11 md:h-9 w-full min-w-0 rounded-none border-0 border-b bg-transparent px-0 py-1 font-medium transition-colors duration-200 outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-(--nx-fg-4) disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
        'focus-visible:border-primary',
        'aria-invalid:border-destructive',
        className,
      )}
      {...props}
    />
  )
}

export { Input }
