import { type NextRequest, NextResponse } from 'next/server'
import { COOKIE_KEY } from './constants'

// Routes that do not require authentication
const PUBLIC_PATHS = [
  '/login',
  '/register',
  '/forgot-password',
  '/reset-password',
]

export function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl
  const session      = request.cookies.get(COOKIE_KEY)?.value
  const isAuthed     = !!session

  const isPublicPath = PUBLIC_PATHS.some((p) => pathname === p || pathname.startsWith(p + '/'))

  // Unauthenticated → redirect to login
  if (!isAuthed && !isPublicPath) {
    const loginUrl = new URL('/login', request.url)
    loginUrl.searchParams.set('callbackUrl', pathname)
    return NextResponse.redirect(loginUrl)
  }

  // Already authenticated → redirect away from auth pages
  if (isAuthed && isPublicPath) {
    return NextResponse.redirect(new URL('/dashboard', request.url))
  }

  return NextResponse.next()
}

export const config = {
  matcher: [
    // Skip Next.js internals, static files, and API routes
    '/((?!_next/static|_next/image|favicon.ico|api/).*)',
  ],
}
