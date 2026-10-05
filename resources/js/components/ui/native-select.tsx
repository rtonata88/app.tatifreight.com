import { ChevronDownIcon } from "lucide-react"
import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * A native <select> styled to match the bare Nexus input: no box, one hairline beneath. Prefer this for forms and
 * filters: it supports an empty "All" option, works with Inertia's useForm
 * directly, and behaves well on phones.
 */
function NativeSelect({
  className,
  children,
  ...props
}: React.ComponentProps<"select">) {
  return (
    <div className="relative w-full">
      <select
        data-slot="native-select"
        className={cn(
          "border-input text-base md:text-body text-foreground flex h-11 md:h-9 w-full min-w-0 appearance-none rounded-none border-0 border-b bg-transparent py-1 pr-7 pl-0 font-medium transition-colors duration-200 outline-none focus-visible:border-primary aria-invalid:border-destructive disabled:cursor-not-allowed disabled:opacity-50 [&>option]:bg-background [&>option]:text-foreground",
          className
        )}
        {...props}
      >
        {children}
      </select>
      <ChevronDownIcon className="text-muted-foreground pointer-events-none absolute top-1/2 right-0.5 size-3.5 -translate-y-1/2 opacity-60" />
    </div>
  )
}

export { NativeSelect }
