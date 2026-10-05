import * as React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'
import { Slot } from 'radix-ui'

/* Nexus badge: counts, categories, tags and reference codes. Flat 4px corners, wash fill,
   11px semibold. Record state is never a badge — that is the status pill. */
const badgeVariants = cva(
  'inline-flex w-fit shrink-0 items-center justify-center gap-1.5 overflow-hidden rounded-sm border border-transparent px-2 py-0.5 text-micro leading-[1.7] font-semibold whitespace-nowrap transition-colors focus-visible:ring-1 focus-visible:ring-ring [&>svg]:pointer-events-none [&>svg]:size-3',
  {
    variants: {
      variant: {
        default: 'bg-secondary text-muted-foreground',
        secondary: 'bg-secondary text-muted-foreground',
        primary: 'bg-(--nx-brass-wash-2) text-(--nx-brass-lo)',
        success: 'bg-(--nx-pos-wash) text-success',
        warning: 'bg-(--nx-warn-wash) text-warning',
        info: 'bg-(--nx-info-wash) text-info',
        destructive: 'bg-(--nx-neg-wash) text-destructive',
        outline: 'border-border text-foreground',
        ghost: '[a&]:hover:bg-accent [a&]:hover:text-accent-foreground',
        link: 'text-primary underline-offset-4 [a&]:hover:underline',
      },
      mono: {
        true: 'font-mono tabular-nums',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
)

function Badge({
  className,
  variant = 'default',
  mono,
  asChild = false,
  ...props
}: React.ComponentProps<'span'> & VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot.Root : 'span'

  return (
    <Comp
      data-slot="badge"
      data-variant={variant}
      className={cn(badgeVariants({ variant, mono }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
