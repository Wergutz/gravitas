; Script do Inno Setup (https://jrsoftware.org/isinfo.php) para gerar o
; instalador do Gerenciador de Disquetes Virtuais Yamaha.
;
; Como compilar localmente no Windows:
;   1. Instale o Inno Setup (https://jrsoftware.org/isdl.php).
;   2. Publique o app primeiro:
;        dotnet publish ..\src\YamahaFloppy.App\YamahaFloppy.App.csproj -c Release ^
;          -r win-x64 --self-contained true -p:PublishSingleFile=true ^
;          -p:IncludeNativeLibrariesForSelfExtract=true
;   3. Baixe o driver do Dokan (embutido no instalador, ver [Files]/[Run]
;      abaixo) para installer\vendor\Dokan_x64.msi:
;        https://github.com/dokan-dev/dokany/releases/latest/download/Dokan_x64.msi
;   4. Abra este arquivo no Inno Setup e clique em "Compile" (ou rode
;      ISCC.exe YamahaFloppyManager.iss pela linha de comando).
;   5. O instalador é gerado em installer\output\.
;
; O workflow do GitHub Actions (.github/workflows/yamaha-floppy-installer.yml)
; faz esses mesmos passos automaticamente e disponibiliza o .exe pronto,
; sem precisar de uma máquina Windows.

#ifndef MyAppVersion
  #define MyAppVersion "1.0.0"
#endif

#define MyAppName "Gerenciador de Disquetes Virtuais Yamaha"
#define MyAppPublisher "Wergutz"
#define MyAppExeName "YamahaFloppyManager.exe"
#define MyAppPublishDir "..\src\YamahaFloppy.App\bin\Release\net8.0-windows\win-x64\publish"

[Setup]
AppId={{B5B6C8B0-6D2E-4C8E-9B0D-3F6B6D7B9F1A}
AppName={#MyAppName}
AppVersion={#MyAppVersion}
AppPublisher={#MyAppPublisher}
DefaultDirName={autopf}\YamahaFloppyManager
DefaultGroupName=Yamaha Floppy Manager
DisableProgramGroupPage=yes
OutputDir=output
OutputBaseFilename=YamahaFloppyManager-Setup-{#MyAppVersion}
Compression=lzma2
SolidCompression=yes
ArchitecturesAllowed=x64
ArchitecturesInstallIn64BitMode=x64
; A formatação de disco exige administrador; o instalador também roda
; elevado para poder gravar em Arquivos de Programas.
PrivilegesRequired=admin
WizardStyle=modern
UninstallDisplayIcon={app}\{#MyAppExeName}

[Languages]
Name: "brazilianportuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"
Name: "english"; MessagesFile: "compiler:Default.isl"

[Tasks]
Name: "desktopicon"; Description: "Criar um atalho na Área de Trabalho"; GroupDescription: "Atalhos adicionais:"

[Files]
; Todo o conteúdo publicado (exe self-contained single-file + arquivos de apoio, se houver).
Source: "{#MyAppPublishDir}\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs
; Driver do Dokan (baixado pelo workflow do GitHub Actions antes de compilar o
; instalador — ver .github/workflows/yamaha-floppy-installer.yml). Vai só para
; a pasta temporária: é instalado pelo [Run] abaixo e não fica em {app}.
Source: "vendor\Dokan_x64.msi"; DestDir: "{tmp}"; Flags: deleteafterinstall

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\Desinstalar {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
; Instala o driver do Dokan silenciosamente (msiexec /quiet), necessário para
; o botão "Montar no Explorer". Roda antes da tela de conclusão; o instalador
; já está elevado (PrivilegesRequired=admin), então o msiexec herda isso.
; Rodar de novo com a mesma versão já instalada é inofensivo (o MSI só repara).
Filename: "msiexec.exe"; Parameters: "/i ""{tmp}\Dokan_x64.msi"" /quiet /norestart"; StatusMsg: "Instalando driver Dokan (necessário para montar disquetes no Explorer)..."; Flags: waituntilterminated

; shellexec: usa ShellExecute (mesmo mecanismo do Explorer) em vez de
; CreateProcess. Necessário porque o .exe exige elevação (requireAdministrator
; no seu manifest, para poder formatar disco) — CreateProcess não sabe mostrar
; o prompt do UAC e falha com "erro 740: operação requer elevação".
Filename: "{app}\{#MyAppExeName}"; Description: "Abrir {#MyAppName} agora"; Flags: nowait postinstall skipifsilent shellexec
