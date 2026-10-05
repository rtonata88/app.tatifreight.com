import * as React from 'react'
import { Switch as SwitchPrimitive } from 'radix-ui'
import { cn } from '@/lib/utils'

/* Nexus switch: a 34×18 hairline pill. Checked fills with the accent wash and moves the
   thumb by margin (the system never animates transform). */
function Switch({ className, ...props }: React.ComponentProps<typeof SwitchPrimitive.Root>) {
  return (
    <SwitchPrimitive.Root
      data-slot="switch"
      className={cn(
        'peer border-input focus-visible:ring-ring/50 data-[state=checked]:border-primary inline-flex h-[18px] w-[34px] shrink-0 items-center rounded-full border bg-transparent transition-colors duration-200 outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-(--nx-brass-wash-2)',
        className,
      )}
      {...props}
    >
      <SwitchPrimitive.Thumb
        data-slot="switch-thumb"
        className="bg-muted-foreground data-[state=checked]:bg-primary pointer-events-none block size-3 rounded-full transition-[margin] duration-200 data-[state=checked]:ml-[18px] data-[state=unchecked]:ml-[2px]"
      />
    </SwitchPrimitive.Root>
  )
}

export { Switch }
