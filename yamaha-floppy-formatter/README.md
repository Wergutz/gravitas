# Gerenciador de Disquetes Virtuais Yamaha

Programa para Windows 11 que formata um pendrive e cria múltiplos **disquetes
virtuais** (arquivos de imagem FAT12 de 720KB ou 1.44MB) para uso com
emuladores de disquete estilo **Gotek/FlashFloppy** (ou compatíveis) ligados
a teclados Yamaha. Permite importar, exportar e apagar arquivos de dentro de
cada disquete virtual pelos botões do editor, ou **montar o disquete como uma
unidade de verdade no Explorer** (via [Dokan](https://github.com/dokan-dev/dokany))
para arrastar e soltar arquivos normalmente — sem precisar de outro programa.

## Como funciona (importante entender antes de usar)

Emuladores de disquete desse tipo **não leem várias partições reais** no
pendrive. Eles leem **um único pendrive formatado em FAT** e, dentro dele,
vários **arquivos de imagem** nomeados `DSKA0000.IMG`, `DSKA0001.IMG`,
`DSKA0002.IMG`, etc. — cada arquivo é um "disquete" completo (com seu próprio
boot sector, FAT e diretório), e o hardware do emulador troca entre eles pelo
botão/chave seletora, na ordem dos nomes dos arquivos.

Por isso "criar as partições ao formatar", pedido inicialmente, corresponde
na prática a:
1. Formatar o pendrive inteiro (ou os primeiros ~2GB dele, se for maior) como
   **uma única partição FAT16**. FAT16 é obrigatório porque o firmware
   original do Gotek (sem FlashFloppy) só reconhece pendrives FAT12/FAT16 —
   em FAT32 ele lista os arquivos, mas não consegue ler o conteúdo certo.
2. Criar dentro dela **N arquivos `DSKA####.IMG`**, cada um já formatado
   internamente como um disquete FAT12 válido (720KB ou 1.44MB, à escolha).

Isso é exatamente o que o programa faz.

## Estrutura do projeto

```
yamaha-floppy-formatter/
├── YamahaFloppyFormatter.sln
├── src/
│   ├── YamahaFloppy.Core/       # Biblioteca multiplataforma (sem dependência de Windows)
│   │   ├── Fat12Image.cs        # Cria/lê/grava imagens de disquete FAT12
│   │   ├── EmulatorVolume.cs    # Gerencia os arquivos DSKA####.IMG na pasta do pendrive
│   │   ├── FloppyFormat.cs      # Geometria dos formatos 720KB/1.44MB
│   │   ├── FatShortName.cs      # Validação de nomes de arquivo 8.3 (DOS)
│   │   └── FloppyDokanOperations.cs # Implementa IDokanOperations sobre um Fat12Image
│   └── YamahaFloppy.App/        # Aplicativo WPF (Windows apenas)
│       ├── MainWindow.xaml(.cs)       # Selecionar pendrive, formatar, listar disquetes
│       ├── FloppyEditorWindow.xaml(.cs) # Importar/exportar/apagar arquivos de um disquete
│       └── Services/
│           ├── UsbDriveService.cs   # Detecção USB (WMI) e formatação (diskpart)
│           ├── FloppyMountService.cs # Monta/desmonta disquetes virtuais como unidade (Dokan)
│           └── DokanAvailability.cs  # Verifica se o driver do Dokan está instalado
└── tests/
    └── YamahaFloppy.Core.Tests/ # Testes automatizados da lógica FAT12 (rodam em qualquer SO)
```

`YamahaFloppy.Core` não depende de nada específico do Windows e foi testado
neste ambiente (Linux) com `dotnet test` — os testes cobrem criação de
imagens, importação/exportação/remoção de arquivos, leitura/escrita por
offset, truncamento, renomeação, nomes inválidos, disco cheio, diretório raiz
cheio, e a implementação de `IDokanOperations` (com um stub próprio de
`IDokanFileInfo`, já que o `MockDokanFileInfo` do próprio DokanNet só funciona
no Windows).

`YamahaFloppy.App` (a interface gráfica) **só compila e roda no Windows**,
pois usa WPF, Windows Forms (para o seletor de pasta) e WMI/diskpart para
detectar e formatar discos. Não foi possível compilar essa parte neste
ambiente de desenvolvimento (Linux); revise/compile-a no Windows antes do
primeiro uso real (veja abaixo).

## Como obter o instalador (.exe) — sem precisar de um Windows para compilar

Este repositório tem um workflow do GitHub Actions
(`.github/workflows/yamaha-floppy-installer.yml`) que compila o app e gera
o instalador automaticamente em um runner Windows:

1. No GitHub, abra a aba **Actions** do repositório.
2. Selecione o workflow **"Build Yamaha Floppy Manager Installer"**.
3. Clique em **"Run workflow"** (ou apenas espere: ele roda sozinho a cada
   push que mexe em `yamaha-floppy-formatter/`).
4. Quando terminar, abra a execução e baixe o artifact
   **"YamahaFloppyManager-Setup"** — dentro dele está o
   `YamahaFloppyManager-Setup-x.x.x.exe`.
5. Copie esse `.exe` para o Windows 11 onde você vai usar o programa e
   rode-o — ele instala o app (com atalho no Menu Iniciar e, se marcado, na
   Área de Trabalho) e já pergunta se quer abrir o programa ao final.

O instalador é gerado com [Inno Setup](https://jrsoftware.org/isinfo.php) a
partir do script `installer/YamahaFloppyManager.iss`.

## Como compilar (no Windows 11)

Pré-requisitos: [.NET 8 SDK](https://dotnet.microsoft.com/download) instalado.

```powershell
cd yamaha-floppy-formatter
dotnet build
```

Para gerar um executável único para distribuir:

```powershell
dotnet publish src\YamahaFloppy.App\YamahaFloppy.App.csproj -c Release -r win-x64 --self-contained true -p:PublishSingleFile=true
```

O executável (`YamahaFloppyManager.exe`) fica em
`src\YamahaFloppy.App\bin\Release\net8.0-windows\win-x64\publish\`.

### Gerar o instalador localmente (alternativa ao GitHub Actions)

1. Instale o [Inno Setup](https://jrsoftware.org/isdl.php).
2. Rode o `dotnet publish` acima (precisa existir a pasta `publish\`).
3. Compile o instalador:
   ```powershell
   & "C:\Program Files (x86)\Inno Setup 6\ISCC.exe" installer\YamahaFloppyManager.iss
   ```
4. O instalador aparece em `installer\output\YamahaFloppyManager-Setup-1.0.0.exe`.

## Como rodar os testes

```powershell
dotnet test tests\YamahaFloppy.Core.Tests\YamahaFloppy.Core.Tests.csproj
```

## Uso

1. Abra o programa **como administrador** (ele já pede elevação via UAC
   automaticamente — formatar disco exige privilégios de administrador no
   Windows).
2. Conecte o pendrive. Clique em **"Atualizar lista"** e selecione-o na
   lista (confira modelo/tamanho — só pendrives USB aparecem).
3. Escolha o tamanho do disquete (1.44MB ou 720KB — use o mesmo tamanho que
   o seu teclado Yamaha espera) e a quantidade de disquetes virtuais.
4. Marque a caixa de confirmação e clique em **"Formatar pendrive e criar
   disquetes virtuais"**. **Isso apaga todos os dados do pendrive.**
5. Depois de formatado, a lista de disquetes virtuais aparece. Você pode:
   - Dar duplo clique em um disco para abrir o editor e **importar**,
     **exportar** ou **apagar** arquivos dele; ou
   - Selecionar um disco e clicar em **"Montar no Explorer"** para abri-lo
     como uma unidade de verdade (ex.: `Z:\`) e arrastar arquivos normalmente
     — clique em **"Desmontar"** depois para gravar as mudanças de volta no
     disquete virtual. Isso exige o driver do
     [Dokan](https://github.com/dokan-dev/dokany/releases) instalado (ver
     abaixo); sem ele, use o editor pelos botões.
6. Da próxima vez, não precisa formatar de novo: use **"Abrir pendrive já
   preparado..."** e aponte para a unidade/pasta do pendrive.

### Montar disquetes no Explorer (opcional)

O botão **"Montar no Explorer"** precisa do driver do
[Dokan](https://github.com/dokan-dev/dokany) instalado no Windows — é um
driver de sistema de arquivos em modo usuário, de código aberto, usado por
ferramentas como o rclone e o sshfs-win. Baixe e instale o `DokanSetup` mais
recente em https://github.com/dokan-dev/dokany/releases (uma vez só; não
precisa reinstalar a cada disquete montado). Se o driver não estiver
instalado, o programa avisa e sugere usar o editor pelos botões em vez disso.

## Avisos de segurança

- **Formatar um pendrive apaga todos os dados dele permanentemente.** O
  programa só lista discos conectados via USB (nunca discos internos), exige
  uma confirmação explícita antes de formatar, e reconfirma o dispositivo
  físico (não apenas o número do disco, que pode mudar) imediatamente antes
  de rodar o diskpart.
- Ainda assim, **cheque o modelo e o tamanho do pendrive na lista antes de
  confirmar** — é a sua garantia final de que está formatando o disco certo.
- O programa precisa rodar como administrador porque formatação de disco no
  Windows exige isso; isso é esperado e normal para esse tipo de ferramenta.

## Compatibilidade

Os tamanhos, boot sector e estrutura FAT12 gerados seguem exatamente o mesmo
padrão usado pelo MS-DOS/Windows ao formatar disquetes reais de 720KB e
1.44MB, e os arquivos usam a convenção de nomes `DSKA####.IMG` adotada pelos
emuladores de disquete HxC/Gotek/FlashFloppy mais comuns vendidos para
teclados Yamaha. Se o seu emulador específico usar outra extensão (ex.:
`.HFE` em vez de `.IMG`) ou outra convenção de nomes, me avise para eu
ajustar `EmulatorVolume.FileExtension`/`FileNamePrefix`.
