using System.Security.AccessControl;
using System.Security.Principal;
using DokanNet;
using FileAccess = DokanNet.FileAccess;

namespace YamahaFloppy.Core;

/// <summary>
/// Implementa <see cref="IDokanOperations"/> para expor um <see cref="Fat12Image"/> como uma
/// unidade real do Windows via Dokan — permite abrir o disquete virtual no Explorer e
/// arrastar/soltar arquivos nele, em vez de usar os botões Importar/Exportar do editor.
///
/// O disquete FAT12 é sempre um diretório raiz "achatado" (sem subpastas), então todo
/// caminho de arquivo aqui é o próprio nome 8.3 prefixado por "\" (ex.: "\SONG1.MID").
/// Cada chamada consulta o <see cref="Fat12Image"/> diretamente pelo nome — não há handles
/// nem estado por arquivo, o que mantém a implementação simples e permite testá-la com um
/// stub de <see cref="IDokanFileInfo"/>, sem precisar do driver do Dokan instalado.
///
/// Erros são mapeados manualmente (<see cref="MapException"/>) em vez de usar
/// <see cref="DokanHelper.ToNtStatus"/>: apesar do nome, esse helper faz P/Invoke na DLL
/// nativa do Dokan (dokan2.dll) só para converter o código de erro — ou seja, teria a
/// mesma dependência nativa que estamos evitando ao manter essa classe testável fora do
/// Windows.
/// </summary>
public sealed class FloppyDokanOperations : IDokanOperations
{
    private readonly Fat12Image _image;
    private readonly string _volumeLabel;
    private readonly object _lock = new();

    public FloppyDokanOperations(Fat12Image image, string volumeLabel = "YAMAHA")
    {
        _image = image;
        _volumeLabel = volumeLabel;
    }

    /// <summary>Converte o caminho do Dokan ("\NOME.EXT" ou "\") no nome 8.3, ou null para a raiz.</summary>
    private static string? NormalizeName(string fileName)
    {
        var trimmed = fileName.TrimStart('\\');
        return trimmed.Length == 0 ? null : trimmed;
    }

    public NtStatus CreateFile(string fileName, FileAccess access, FileShare share, FileMode mode,
        FileOptions options, FileAttributes attributes, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null)
        {
            info.IsDirectory = true;
            return DokanResult.Success;
        }

        lock (_lock)
        {
            try
            {
                var existing = _image.FindFile(name);

                switch (mode)
                {
                    case FileMode.CreateNew:
                        if (existing is not null)
                            return DokanResult.FileExists;
                        _image.AddFile(name, Array.Empty<byte>());
                        break;

                    case FileMode.Create:
                        _image.AddFile(name, Array.Empty<byte>(), overwrite: existing is not null);
                        break;

                    case FileMode.Truncate:
                        if (existing is null)
                            return DokanResult.FileNotFound;
                        _image.SetFileLength(name, 0);
                        break;

                    case FileMode.Open:
                        if (existing is null)
                            return DokanResult.FileNotFound;
                        break;

                    case FileMode.OpenOrCreate:
                    case FileMode.Append:
                        if (existing is null)
                            _image.AddFile(name, Array.Empty<byte>());
                        break;
                }

                return DokanResult.Success;
            }
            catch (Exception ex)
            {
                return MapException(ex);
            }
        }
    }

    public void Cleanup(string fileName, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null || !info.DeletePending)
            return;

        lock (_lock)
        {
            if (_image.ContainsFile(name))
                _image.DeleteFile(name);
        }
    }

    public void CloseFile(string fileName, IDokanFileInfo info)
    {
        // Sem handles/estado por arquivo para liberar: cada chamada já opera direto no
        // Fat12Image pelo nome.
    }

    public NtStatus ReadFile(string fileName, byte[] buffer, out int bytesRead, long offset, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null)
        {
            bytesRead = 0;
            return DokanResult.Error;
        }

        lock (_lock)
        {
            try
            {
                bytesRead = _image.ReadFileData(name, offset, buffer, 0, buffer.Length);
                return DokanResult.Success;
            }
            catch (Exception ex)
            {
                bytesRead = 0;
                return MapException(ex);
            }
        }
    }

    public NtStatus WriteFile(string fileName, byte[] buffer, out int bytesWritten, long offset, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null)
        {
            bytesWritten = 0;
            return DokanResult.Error;
        }

        lock (_lock)
        {
            try
            {
                var actualOffset = info.WriteToEndOfFile
                    ? _image.FindFile(name)?.SizeBytes ?? 0
                    : offset;

                _image.WriteFileData(name, actualOffset, buffer, 0, buffer.Length);
                bytesWritten = buffer.Length;
                return DokanResult.Success;
            }
            catch (Exception ex)
            {
                bytesWritten = 0;
                return MapException(ex);
            }
        }
    }

    public NtStatus FlushFileBuffers(string fileName, IDokanFileInfo info) => DokanResult.Success;

    public NtStatus GetFileInformation(string fileName, out FileInformation fileInfo, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null)
        {
            fileInfo = new FileInformation
            {
                FileName = "\\",
                Attributes = FileAttributes.Directory,
                Length = 0,
            };
            return DokanResult.Success;
        }

        lock (_lock)
        {
            var entry = _image.FindFile(name);
            if (entry is null)
            {
                fileInfo = default;
                return DokanResult.FileNotFound;
            }

            fileInfo = ToFileInformation(entry);
            return DokanResult.Success;
        }
    }

    public NtStatus FindFiles(string fileName, out IList<FileInformation> files, IDokanFileInfo info)
    {
        lock (_lock)
        {
            files = _image.ListFiles().Select(ToFileInformation).ToList();
            return DokanResult.Success;
        }
    }

    public NtStatus FindFilesWithPattern(string fileName, string searchPattern, out IList<FileInformation> files,
        IDokanFileInfo info)
    {
        lock (_lock)
        {
            files = _image.ListFiles()
                .Where(e => DokanHelper.DokanIsNameInExpression(searchPattern, e.Name, ignoreCase: true))
                .Select(ToFileInformation)
                .ToList();
            return DokanResult.Success;
        }
    }

    public NtStatus SetFileAttributes(string fileName, FileAttributes attributes, IDokanFileInfo info) =>
        DokanResult.Success;

    public NtStatus SetFileTime(string fileName, DateTime? creationTime, DateTime? lastAccessTime,
        DateTime? lastWriteTime, IDokanFileInfo info) => DokanResult.Success;

    public NtStatus DeleteFile(string fileName, IDokanFileInfo info)
    {
        var name = NormalizeName(fileName);
        if (name is null)
            return DokanResult.AccessDenied;

        lock (_lock)
            return _image.ContainsFile(name) ? DokanResult.Success : DokanResult.FileNotFound;
    }

    public NtStatus DeleteDirectory(string fileName, IDokanFileInfo info) => DokanResult.AccessDenied;

    public NtStatus MoveFile(string oldName, string newName, bool replace, IDokanFileInfo info)
    {
        var oldFile = NormalizeName(oldName);
        var newFile = NormalizeName(newName);
        if (oldFile is null || newFile is null)
            return DokanResult.AccessDenied;

        lock (_lock)
        {
            try
            {
                if (replace && _image.ContainsFile(newFile))
                    _image.DeleteFile(newFile);

                _image.RenameFile(oldFile, newFile);
                return DokanResult.Success;
            }
            catch (Exception ex)
            {
                return MapException(ex);
            }
        }
    }

    public NtStatus SetEndOfFile(string fileName, long length, IDokanFileInfo info) => SetLength(fileName, length);

    public NtStatus SetAllocationSize(string fileName, long length, IDokanFileInfo info) => SetLength(fileName, length);

    private NtStatus SetLength(string fileName, long length)
    {
        var name = NormalizeName(fileName);
        if (name is null)
            return DokanResult.AccessDenied;

        lock (_lock)
        {
            try
            {
                _image.SetFileLength(name, length);
                return DokanResult.Success;
            }
            catch (Exception ex)
            {
                return MapException(ex);
            }
        }
    }

    public NtStatus LockFile(string fileName, long offset, long length, IDokanFileInfo info) => DokanResult.Success;

    public NtStatus UnlockFile(string fileName, long offset, long length, IDokanFileInfo info) => DokanResult.Success;

    public NtStatus GetDiskFreeSpace(out long freeBytesAvailable, out long totalNumberOfBytes,
        out long totalNumberOfFreeBytes, IDokanFileInfo info)
    {
        lock (_lock)
        {
            freeBytesAvailable = _image.FreeSpaceBytes;
            totalNumberOfBytes = _image.TotalCapacityBytes;
            totalNumberOfFreeBytes = _image.FreeSpaceBytes;
            return DokanResult.Success;
        }
    }

    public NtStatus GetVolumeInformation(out string volumeLabel, out FileSystemFeatures features,
        out string fileSystemName, out uint maximumComponentLength, IDokanFileInfo info)
    {
        volumeLabel = _volumeLabel;
        features = FileSystemFeatures.CasePreservedNames | FileSystemFeatures.UnicodeOnDisk;
        fileSystemName = "FAT12";
        maximumComponentLength = 11;
        return DokanResult.Success;
    }

    public NtStatus GetFileSecurity(string fileName, out FileSystemSecurity? security,
        AccessControlSections sections, IDokanFileInfo info)
    {
        security = null;
        return DokanResult.NotImplemented;
    }

    public NtStatus SetFileSecurity(string fileName, FileSystemSecurity security, AccessControlSections sections,
        IDokanFileInfo info) => DokanResult.NotImplemented;

    public NtStatus Mounted(string mountPoint, IDokanFileInfo info) => DokanResult.Success;

    public NtStatus Unmounted(IDokanFileInfo info) => DokanResult.Success;

    public NtStatus FindStreams(string fileName, out IList<FileInformation> streams, IDokanFileInfo info)
    {
        streams = Array.Empty<FileInformation>();
        return DokanResult.NotImplemented;
    }

    /// <summary>
    /// Mapeia as exceções que o Fat12Image lança para o NtStatus equivalente que o Dokan
    /// espera. Ver o comentário na declaração da classe sobre por que isso não usa
    /// <see cref="DokanHelper.ToNtStatus"/>.
    /// </summary>
    private static NtStatus MapException(Exception ex) => ex switch
    {
        FileNotFoundException => DokanResult.FileNotFound,
        InvalidOperationException => DokanResult.FileExists,
        IOException => DokanResult.DiskFull,
        ArgumentException => DokanResult.InvalidName,
        _ => DokanResult.Error,
    };

    private static FileInformation ToFileInformation(FloppyDirectoryEntry entry) => new()
    {
        FileName = entry.Name,
        Attributes = FileAttributes.Archive
            | (entry.IsReadOnly ? FileAttributes.ReadOnly : 0)
            | (entry.IsHidden ? FileAttributes.Hidden : 0),
        CreationTime = entry.Timestamp,
        LastAccessTime = entry.Timestamp,
        LastWriteTime = entry.Timestamp,
        Length = entry.SizeBytes,
    };
}
