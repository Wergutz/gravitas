namespace YamahaFloppy.Core;

/// <summary>
/// Gerencia a coleção de arquivos de imagem de disquete ("disquetes virtuais")
/// dentro da pasta raiz de um pendrive já formatado. Segue a convenção de nomes
/// "DSKA0000.IMG", "DSKA0001.IMG", ... usada pelo firmware dos emuladores de
/// disquete (compatíveis HxC/Gotek/FlashFloppy) vendidos para teclados Yamaha:
/// o emulador lista os arquivos da raiz do pendrive em ordem e associa cada um
/// a uma posição no seletor de disco do hardware.
///
/// Importante: esses emuladores NÃO leem partições de disco separadas — eles
/// leem múltiplos arquivos de imagem dentro de uma única partição FAT do
/// pendrive. "Criar as partições" pedido pelo usuário corresponde, na prática,
/// a formatar o pendrive com uma única partição e, dentro dela, gerar um
/// arquivo de imagem por "disquete".
/// </summary>
public static class EmulatorVolume
{
    public const string FileNamePrefix = "DSKA";
    public const string FileExtension = ".IMG";
    private const int IndexDigits = 4;

    public static string BuildFileName(int index)
    {
        if (index < 0 || index >= Math.Pow(10, IndexDigits))
            throw new ArgumentOutOfRangeException(nameof(index), $"Índice de disco deve estar entre 0 e {Math.Pow(10, IndexDigits) - 1}.");
        return $"{FileNamePrefix}{index.ToString().PadLeft(IndexDigits, '0')}{FileExtension}";
    }

    public static bool TryParseIndex(string fileName, out int index)
    {
        index = -1;
        var ext = Path.GetExtension(fileName);
        if (!ext.Equals(FileExtension, StringComparison.OrdinalIgnoreCase))
            return false;

        var name = Path.GetFileNameWithoutExtension(fileName);
        if (!name.StartsWith(FileNamePrefix, StringComparison.OrdinalIgnoreCase))
            return false;

        var digits = name[FileNamePrefix.Length..];
        return digits.Length == IndexDigits && int.TryParse(digits, out index) && index >= 0;
    }

    /// <summary>Cria <paramref name="count"/> disquetes virtuais em branco na pasta indicada.</summary>
    public static IReadOnlyList<FloppySlot> CreateDisks(string folderPath, int count, FloppyFormat format, int startIndex = 0)
    {
        if (count <= 0)
            throw new ArgumentOutOfRangeException(nameof(count), "A quantidade de disquetes deve ser maior que zero.");

        Directory.CreateDirectory(folderPath);
        var created = new List<FloppySlot>(count);

        for (var i = 0; i < count; i++)
        {
            var index = startIndex + i;
            var fileName = BuildFileName(index);
            var path = Path.Combine(folderPath, fileName);

            if (File.Exists(path))
                throw new IOException($"Já existe um disquete virtual de índice {index} ({fileName}) nesta pasta.");

            var image = Fat12Image.CreateNew(format, $"YAMAHA{index:D2}");
            image.Save(path);

            created.Add(new FloppySlot(index, path, image.TotalCapacityBytes, format));
        }

        return created;
    }

    /// <summary>Lista os disquetes virtuais já existentes na pasta, ordenados por índice.</summary>
    public static IReadOnlyList<FloppySlot> ListSlots(string folderPath)
    {
        if (!Directory.Exists(folderPath))
            return Array.Empty<FloppySlot>();

        var slots = new List<FloppySlot>();
        foreach (var file in Directory.EnumerateFiles(folderPath, $"{FileNamePrefix}*{FileExtension}", SearchOption.TopDirectoryOnly))
        {
            var fileName = Path.GetFileName(file);
            if (!TryParseIndex(fileName, out var index))
                continue;

            var size = new FileInfo(file).Length;
            FloppyFormat? format = size switch
            {
                1_474_560 => FloppyFormat.Hd1440,
                737_280 => FloppyFormat.Dd720,
                _ => null,
            };

            slots.Add(new FloppySlot(index, file, size, format));
        }

        slots.Sort((a, b) => a.Index.CompareTo(b.Index));
        return slots;
    }

    public static Fat12Image OpenDisk(FloppySlot slot)
    {
        if (slot.Format is null)
            throw new NotSupportedException($"O arquivo '{slot.FileName}' não tem um tamanho de disquete reconhecido.");
        return Fat12Image.Load(File.ReadAllBytes(slot.FilePath));
    }

    public static void SaveDisk(FloppySlot slot, Fat12Image image) => image.Save(slot.FilePath);
}
