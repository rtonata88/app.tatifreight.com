import * as React from 'react'
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'
import { Slot } from 'radix-ui'

/* Nexus buttons. Phones get 44px tap targets; from md up the dense desktop sizes apply.
   Nexus buttons. `default` is the primary (solid accent — one per region), `outline` is the
   ghost hairline workhorse, `link` is the inline text action, `destructive` is the danger outline.
   Hover only changes colour; nothing moves. Focus is a 1px accent ring, never a glow. */
const buttonVariants = cva(
  "inline-flex shrink-0 items-center justify-center gap-2 rounded-md border text-body font-semibold tracking-[0.01em] whitespace-nowrap transition-colors duration-200 outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-40 aria-invalid:border-destructive [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-[15px]",
  {
    variants: {
      variant: {
        default:
          'border-primary bg-primary text-primary-foreground hover:border-(--nx-brass-hi) hover:bg-(--nx-brass-hi) active:border-(--nx-brass-lo) active:bg-(--nx-brass-lo)',
        outline:
          'border-input bg-transparent text-foreground hover:border-primary hover:text-primary',
        secondary:
          'border-transparent bg-secondary text-secondary-foreground hover:bg-accent hover:text-accent-foreground',
        ghost:
          'border-transparent bg-transparent text-foreground hover:bg-accent hover:text-accent-foreground',
        link: 'h-auto border-transparent bg-transparent px-1 text-muted-foreground hover:text-primary',
        destructive: 'border-destructive bg-transparent text-destructive hover:bg-(--nx-neg-wash)',
      },
      size: {
        default: 'h-11 px-4 md:h-9 md:px-3.5',
        xs: "h-8 gap-1 px-2 text-micro md:h-6 [&_svg:not([class*='size-'])]:size-3",
        sm: "h-10 gap-1.5 px-3 text-body md:h-7 md:px-2.5 md:text-micro [&_svg:not([class*='size-'])]:size-3.5",
        lg: 'h-11 px-5 text-sm',
        icon: 'size-11 md:size-9',
        'icon-xs': "size-8 md:size-6 [&_svg:not([class*='size-'])]:size-3",
        'icon-sm': "size-10 md:size-7 [&_svg:not([class*='size-'])]:size-3.5",
        'icon-lg': 'size-11',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
)

function Button({
  className,
  variant = 'default',
  size = 'default',
  asChild = false,
  ...props
}: React.ComponentProps<'button'> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot.Root : 'button'

  return (
    <Comp
      data-slot="button"
      data-variant={variant}
      data-size={size}
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
