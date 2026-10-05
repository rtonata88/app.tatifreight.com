import * as React from 'react'
import { cn } from '@/lib/utils'
import { Label as LabelPrimitive } from 'radix-ui'

/* Nexus label: 11px, 600, uppercase, 0.18em tracking, muted. Sits above a bare input.
   Beside a checkbox or switch it reads as plain body text instead. */
function Label({ className, ...props }: React.ComponentProps<typeof LabelPrimitive.Root>) {
  return (
    <LabelPrimitive.Root
      data-slot="label"
      className={cn(
        'text-micro tracking-label text-muted-foreground flex items-center gap-2 leading-none font-semibold uppercase select-none peer-data-[slot=checkbox]:text-body peer-data-[slot=checkbox]:text-foreground peer-data-[slot=checkbox]:font-medium peer-data-[slot=checkbox]:tracking-normal peer-data-[slot=checkbox]:normal-case peer-data-[slot=switch]:text-body peer-data-[slot=switch]:text-foreground peer-data-[slot=switch]:font-medium peer-data-[slot=switch]:tracking-normal peer-data-[slot=switch]:normal-case group-data-[disabled=true]:pointer-events-none group-data-[disabled=true]:opacity-50 peer-disabled:cursor-not-allowed peer-disabled:opacity-50',
        className,
      )}
      {...props}
    />
  )
}

export { Label }
