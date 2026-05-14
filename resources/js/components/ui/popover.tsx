"use client"

import * as React from "react"

interface PopoverProps {
  children: React.ReactNode
}

const Popover = ({ children }: PopoverProps) => {
  return <div>{children}</div>
}

const PopoverTrigger = ({ children }: { children: React.ReactNode }) => {
  return <>{children}</>
}

const PopoverContent = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement> & { align?: string, sideOffset?: number }
>(({ className, align = "center", sideOffset = 4, ...props }, ref) => (
  <div
    ref={ref}
    className="z-50 w-72 rounded-md border bg-white p-4 shadow-md"
    {...props}
  />
))
PopoverContent.displayName = "PopoverContent"

export { Popover, PopoverTrigger, PopoverContent }