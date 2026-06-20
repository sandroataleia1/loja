# Instalação do PDV — Desktop Offline

O PDV é um aplicativo **desktop** construído com Tauri 2 + Rust + SQLite + React.
Roda em Windows, Linux e macOS. Funciona **offline** — sincroniza com o backend quando há conexão.

---

## O que você precisa instalar (uma vez só)

| Ferramenta | Para que serve | Link |
|-----------|---------------|------|
| Node.js 22+ | Rodar o front React | [nodejs.org](https://nodejs.org) |
| pnpm | Gerenciador de pacotes | `npm i -g pnpm` |
| Rust (via rustup) | Compilar o Tauri | [rustup.rs](https://rustup.rs) |
| Git | Baixar o código | [git-scm.com](https://git-scm.com) |

> **Windows:** instale também o **Microsoft C++ Build Tools** (Visual Studio Build Tools 2022).
> Durante a instalação marque **"Desenvolvimento para desktop com C++"**.
> Download: [visualstudio.microsoft.com/visual-cpp-build-tools](https://visualstudio.microsoft.com/visual-cpp-build-tools/)

---

## Etapa 1 — Instalar o Rust

```bash
# Windows / Linux / macOS
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
```

No Windows, baixe e execute o instalador em [rustup.rs](https://rustup.rs).

Após instalar, abra um novo terminal e confirme:
```bash
rustc --version
# Esperado: rustc 1.77.2 ou superior
```

---

## Etapa 2 — Clonar o repositório

```bash
git clone https://github.com/sandroataleia1/loja.git
cd loja/pdv
```

---

## Etapa 3 — Instalar dependências

```bash
cd loja/pdv
pnpm install
```

---

## Etapa 4 — Configurar a URL da API

Crie o arquivo `.env` dentro de `pdv/`:

```bash
cp .env.example .env 2>/dev/null || echo "VITE_API_URL=https://api.sualoja.com.br/api/v1" > .env
```

Edite com a URL real do seu backend:
```dotenv
VITE_API_URL=https://api.sualoja.com.br/api/v1
```

> O PDV funciona **offline** mesmo sem conexão — a URL é usada apenas para sincronizar
> vendas e puxar catálogo quando houver internet.

---

## Etapa 5 — Rodar em modo desenvolvimento

```bash
cd loja/pdv
pnpm tauri dev
```

A primeira execução demora mais (Rust compila as dependências). As seguintes são rápidas.

A janela do PDV abre automaticamente com tamanho **1366×768**.

---

## Etapa 6 — Gerar o instalador para distribuição

```bash
cd loja/pdv
pnpm tauri build
```

Os instaladores ficam em:

| Sistema | Arquivo gerado |
|---------|---------------|
| **Windows** | `pdv/src-tauri/target/release/bundle/msi/PDV Construção_0.1.0_x64_en-US.msi` |
| **Windows** | `pdv/src-tauri/target/release/bundle/nsis/PDV Construção_0.1.0_x64-setup.exe` |
| **Linux** | `pdv/src-tauri/target/release/bundle/deb/pdv-construcao_0.1.0_amd64.deb` |
| **Linux** | `pdv/src-tauri/target/release/bundle/appimage/pdv-construcao_0.1.0_amd64.AppImage` |
| **macOS** | `pdv/src-tauri/target/release/bundle/dmg/PDV Construção_0.1.0_x64.dmg` |

> O build gera apenas o instalador para o sistema operacional onde está sendo compilado.
> Para gerar para Windows + Linux ao mesmo tempo é necessário usar CI/CD (ex: GitHub Actions).

---

## Instalar no computador do operador de caixa

Após gerar o instalador:

**Windows:**
1. Copie o `.msi` ou `.exe` para o computador do caixa (pendrive, rede local, etc.)
2. Execute como administrador
3. Siga o assistente de instalação
4. O PDV aparece no menu Iniciar como **"PDV Construção"**

**Linux:**
```bash
# .deb (Ubuntu/Debian)
sudo dpkg -i pdv-construcao_0.1.0_amd64.deb

# .AppImage (qualquer distribuição)
chmod +x pdv-construcao_0.1.0_amd64.AppImage
./pdv-construcao_0.1.0_amd64.AppImage
```

---

## Banco de dados local

O PDV usa **SQLite** armazenado no diretório de dados do usuário:

| Sistema | Localização |
|---------|------------|
| Windows | `C:\Users\<usuario>\AppData\Roaming\com.pdvconstrucao.app\` |
| Linux | `~/.local/share/com.pdvconstrucao.app/` |
| macOS | `~/Library/Application Support/com.pdvconstrucao.app/` |

> O banco é criado automaticamente na primeira execução. Não é necessário configurar nada.

---

## Problemas Comuns

**`error: linker 'link.exe' not found` (Windows)**
→ Instale o **Microsoft C++ Build Tools** conforme descrito nos pré-requisitos.

**`error[E0463]: can't find crate for 'std'`**
→ Execute `rustup update` e tente novamente.

**`pnpm: command not found`**
→ Execute `npm install -g pnpm` e abra um novo terminal.

**`VITE_API_URL` não configurado**
→ Crie o arquivo `.env` dentro de `pdv/` com a URL da API (Etapa 4).

**Janela abre em branco**
→ Aguarde alguns segundos — o React está carregando. Se persistir, verifique o console do Tauri com `pnpm tauri dev`.

---

## Atualizar o PDV

Quando houver nova versão no repositório:

```bash
cd loja/pdv
git pull origin main
pnpm install        # se houver novas dependências JS
pnpm tauri build    # gerar novo instalador
```

Distribua o novo instalador para os caixas. O SQLite local é preservado — as vendas offline não são perdidas.
