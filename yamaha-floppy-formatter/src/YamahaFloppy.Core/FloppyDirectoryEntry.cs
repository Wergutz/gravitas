namespace YamahaFloppy.Core;

/// <summary>Um arquivo listado no diretório raiz de um disquete virtual.</summary>
public sealed record FloppyDirectoryEntry(
    string Name,
    int SizeBytes,
    int FirstCluster,
    DateTime Timestamp,
    bool IsReadOnly,
    bool IsHidden);
