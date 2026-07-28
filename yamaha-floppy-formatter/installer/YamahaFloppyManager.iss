; Script do Inno Setup (https://jrsoftware.org/isinfo.php) para gerar o
; instalador do Gerenciador de Disquetes Virtuais Yamaha.
;
; Como compilar localmente no Windows:
;   1. Instale o Inno Setup (https://jrsoftware.org/isdl.php).
;   2. Publique o app primeiro:
;        dotnet publish ..\src\YamahaFloppy.App\YamahaFloppy.App.csproj -c Release ^
;          -r win-x64 --self-contained true -p:PublishSingleFile=true ^
;          -p:IncludeNativeLibrariesForSelfExtract=true
;   3. Abra este arquivo no Inno Setup e clique em "Compile" (ou rode
;      ISCC.exe YamahaFloppyManager.iss pela linha de comando).
;   4. O instalador é gerado em installer\output\.
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

[Icons]
Name: "{group}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"
Name: "{group}\Desinstalar {#MyAppName}"; Filename: "{uninstallexe}"
Name: "{autodesktop}\{#MyAppName}"; Filename: "{app}\{#MyAppExeName}"; Tasks: desktopicon

[Run]
Filename: "{app}\{#MyAppExeName}"; Description: "Abrir {#MyAppName} agora"; Flags: nowait postinstall skipifsilent
