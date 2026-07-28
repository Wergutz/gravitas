namespace YamahaFloppy.Core;

/// <summary>
/// Tamanhos de disquete suportados pelos teclados Yamaha e por emuladores
/// compatíveis com Gotek/FlashFloppy (imagens FAT12 "cruas", sem partição MBR).
/// </summary>
public enum FloppyFormat
{
    /// <summary>Disquete HD de 1.44MB (padrão nos teclados Yamaha mais recentes).</summary>
    Hd1440,

    /// <summary>Disquete DD de 720KB (usado em teclados Yamaha mais antigos).</summary>
    Dd720,
}

/// <summary>
/// Geometria fixa de um formato de disquete FAT12, seguindo os mesmos parâmetros
/// usados pelo MS-DOS/Windows ao formatar disquetes reais desses tamanhos. Manter
/// esses valores idênticos aos de um disquete real é o que garante que o teclado
/// Yamaha (ou o firmware do emulador) reconheça a imagem.
/// </summary>
public sealed class FloppyGeometry
{
    public const int SectorSize = 512;

    public required int TotalSectors { get; init; }
    public required int SectorsPerCluster { get; init; }
    public required int ReservedSectors { get; init; }
    public required int NumberOfFats { get; init; }
    public required int SectorsPerFat { get; init; }
    public required int RootEntryCount { get; init; }
    public required byte MediaDescriptor { get; init; }
    public required int SectorsPerTrack { get; init; }
    public required int NumberOfHeads { get; init; }

    public int TotalBytes => TotalSectors * SectorSize;
    public int RootDirSectors => (RootEntryCount * 32 + SectorSize - 1) / SectorSize;
    public int FirstFatSector => ReservedSectors;
    public int FirstRootDirSector => ReservedSectors + NumberOfFats * SectorsPerFat;
    public int FirstDataSector => FirstRootDirSector + RootDirSectors;
    public int ClusterCount => (TotalSectors - FirstDataSector) / SectorsPerCluster;
    public int BytesPerCluster => SectorsPerCluster * SectorSize;

    public static FloppyGeometry For(FloppyFormat format) => format switch
    {
        FloppyFormat.Hd1440 => new FloppyGeometry
        {
            TotalSectors = 2880,
            SectorsPerCluster = 1,
            ReservedSectors = 1,
            NumberOfFats = 2,
            SectorsPerFat = 9,
            RootEntryCount = 224,
            MediaDescriptor = 0xF0,
            SectorsPerTrack = 18,
            NumberOfHeads = 2,
        },
        FloppyFormat.Dd720 => new FloppyGeometry
        {
            TotalSectors = 1440,
            SectorsPerCluster = 2,
            ReservedSectors = 1,
            NumberOfFats = 2,
            SectorsPerFat = 3,
            RootEntryCount = 112,
            MediaDescriptor = 0xF9,
            SectorsPerTrack = 9,
            NumberOfHeads = 2,
        },
        _ => throw new ArgumentOutOfRangeException(nameof(format)),
    };
}
