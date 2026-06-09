import { invoke } from '@tauri-apps/api/core'

/** Wrapper tipado sobre invoke — todo acesso ao SQLite passa por aqui. */
export async function db<T>(
  command: string,
  args?: Record<string, unknown>,
): Promise<T> {
  return invoke<T>(command, args)
}
