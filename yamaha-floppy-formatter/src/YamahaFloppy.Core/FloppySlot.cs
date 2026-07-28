namespace YamahaFloppy.Core;

/// <summary>Um "disquete virtual" (arquivo de imagem) dentro da pasta do pendrive.</summary>
public sealed record FloppySlot(int Index, string FilePath, long SizeBytes, FloppyFormat? Format)
{
    public string FileName => Path.GetFileName(FilePath);

    /// <summary>Nome amigável mostrado na UI, ex.: "Disco 03 (1.44MB)".</summary>
    public string DisplayName => Format switch
    {
        FloppyFormat.Hd1440 => $"Disco {Index:D2} (1.44MB)",
        FloppyFormat.Dd720 => $"Disco {Index:D2} (720KB)",
        _ => $"Disco {Index:D2} (formato desconhecido)",
    };
}
