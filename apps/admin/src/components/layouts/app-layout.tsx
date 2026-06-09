'use client'

import { useState, type ReactNode } from 'react'
import { Sidebar } from './sidebar'
import { Header }  from './header'

export function AppLayout({ children }: { children: ReactNode }) {
  const [sidebarOpen, setSidebarOpen] = useState(false)

  return (
    <div className="flex h-screen overflow-hidden bg-background">
      {/* Desktop sidebar */}
      <Sidebar className="hidden md:flex" />

      {/* Mobile sidebar overlay */}
      {sidebarOpen && (
        <>
          <div
            className="fixed inset-0 z-40 bg-black/50 md:hidden"
            onClick={() => setSidebarOpen(false)}
          />
          <Sidebar className="fixed inset-y-0 left-0 z-50 flex md:hidden" />
        </>
      )}

      {/* Main content */}
      <div className="flex flex-1 flex-col overflow-hidden">
        <Header onToggleSidebar={() => setSidebarOpen((v) => !v)} />
        <main className="flex-1 overflow-y-auto">
          <div className="container mx-auto px-4 py-6 max-w-7xl">
            {children}
          </div>
        </main>
      </div>
    </div>
  )
}
