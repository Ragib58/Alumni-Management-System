import { Outlet } from 'react-router-dom'
import { GraduationCap } from 'lucide-react'

const APP_NAME = import.meta.env.VITE_APP_NAME ?? 'Alumni Event Management'

export function AuthLayout() {
  return (
    <div className="grid min-h-screen lg:grid-cols-2">
      {/* Brand panel */}
      <div className="relative hidden flex-col justify-between bg-primary p-10 text-primary-foreground lg:flex">
        <div className="flex items-center gap-2 text-lg font-semibold">
          <GraduationCap className="h-7 w-7" />
          {APP_NAME}
        </div>
        <div className="space-y-4">
          <h1 className="text-3xl font-bold leading-tight">
            Reconnect. Celebrate. Grow together.
          </h1>
          <p className="max-w-md text-primary-foreground/80">
            A single home for your alumni network — manage members, keep profiles
            up to date, and power your next reunion event.
          </p>
        </div>
        <p className="text-sm text-primary-foreground/60">
          © {new Date().getFullYear()} {APP_NAME}. All rights reserved.
        </p>
      </div>

      {/* Form panel */}
      <div className="flex items-center justify-center p-6 sm:p-10">
        <div className="w-full max-w-md">
          <div className="mb-8 flex items-center gap-2 text-lg font-semibold lg:hidden">
            <GraduationCap className="h-6 w-6 text-primary" />
            {APP_NAME}
          </div>
          <Outlet />
        </div>
      </div>
    </div>
  )
}
