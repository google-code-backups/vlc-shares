; Script generated by the Inno Setup Script Wizard.
; SEE THE DOCUMENTATION FOR DETAILS ON CREATING INNO SETUP SCRIPT FILES!

[Setup]
; NOTE: The value of AppId uniquely identifies this application.
; Do not use the same AppId value in installers for other applications.
; (To generate a new GUID, click Tools | Generate GUID inside the IDE.)
AppId={{2D3CDCAB-4146-42D2-83B6-E52B8BB7CE83}
AppName=VLCShares
AppVersion=0.5.3
;AppVerName=VLCShares 0.5.3
AppPublisher=Ximarx
AppPublisherURL=http://code.google.com/p/vlc-shares/
AppSupportURL=http://code.google.com/p/vlc-shares/
AppUpdatesURL=http://code.google.com/p/vlc-shares/
DefaultDirName={pf}\VLCShares
DefaultGroupName=VLCShares
OutputBaseFilename=vlc-shares
Compression=lzma
SolidCompression=yes
AppMutex=EasyPhpMutex

[Languages]
Name: "english"; MessagesFile: "compiler:Default.isl"
Name: "italian"; MessagesFile: "compiler:Languages\Italian.isl"

[Tasks]
Name: "desktopicon"; Description: "{cm:CreateDesktopIcon}"; GroupDescription: "{cm:AdditionalIcons}"; Flags: unchecked

[Files]
Source: "C:\Documents and Settings\Ximarx\Desktop\easyphp-fullpackage\EasyPHP-5.3.3\EasyPHP-5.3.3.exe"; DestDir: "{app}"; Flags: ignoreversion
Source: "C:\Documents and Settings\Ximarx\Desktop\easyphp-fullpackage\EasyPHP-5.3.3\*"; DestDir: "{app}"; Flags: ignoreversion recursesubdirs createallsubdirs
; NOTE: Don't use "Flags: ignoreversion" on any shared system files

[Icons]
Name: "{group}\VLCShares"; Filename: "{app}\EasyPHP-5.3.3.exe"
Name: "{group}\{cm:UninstallProgram,VLCShares}"; Filename: "{uninstallexe}"
Name: "{commondesktop}\VLCShares"; Filename: "{app}\EasyPHP-5.3.3.exe"; Tasks: desktopicon

[UninstallDelete]
Type: files; Name: "{app}\UpFile.tmp"
Type: files; Name: "{app}\tmp\*"
Type: files; Name: "{app}\mysql\data\ib_logfile0"
Type: files; Name: "{app}\mysql\data\ib_logfile1"
Type: files; Name: "{app}\mysql\data\ibdata1"

[Run]
Filename: {app}\EasyPHP-5.3.3.exe; Parameters: /install; Flags: nowait

