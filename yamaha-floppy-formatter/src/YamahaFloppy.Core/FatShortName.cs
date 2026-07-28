using System.Text;

namespace YamahaFloppy.Core;

/// <summary>
/// Validação e formatação de nomes de arquivo curtos 8.3 (DOS), o único formato
/// de nome que teclados Yamaha e a maioria dos emuladores de disquete entendem.
/// </summary>
public static class FatShortName
{
    private const string ValidExtraChars = "!#$%&'()-@^_`{}~";

    /// <summary>
    /// Converte "song1.mid" em ("SONG1   ", "MID") já no layout de 11 bytes
    /// usado na entrada de diretório. Lança <see cref="ArgumentException"/>
    /// se o nome não puder ser representado em 8.3.
    /// </summary>
    public static (string Name8, string Ext3) Split(string fileName)
    {
        if (string.IsNullOrWhiteSpace(fileName))
            throw new ArgumentException("Nome de arquivo vazio.", nameof(fileName));

        var trimmed = fileName.Trim();
        string baseName;
        string ext;

        var dot = trimmed.LastIndexOf('.');
        if (dot < 0)
        {
            baseName = trimmed;
            ext = string.Empty;
        }
        else
        {
            baseName = trimmed[..dot];
            ext = trimmed[(dot + 1)..];
        }

        baseName = baseName.ToUpperInvariant();
        ext = ext.ToUpperInvariant();

        if (baseName.Length == 0 || baseName.Length > 8)
            throw new ArgumentException($"Nome '{fileName}' precisa ter de 1 a 8 caracteres antes do ponto.", nameof(fileName));
        if (ext.Length > 3)
            throw new ArgumentException($"Extensão de '{fileName}' precisa ter no máximo 3 caracteres.", nameof(fileName));

        foreach (var c in baseName)
            ValidateChar(c, fileName);
        foreach (var c in ext)
            ValidateChar(c, fileName);

        return (baseName, ext);
    }

    private static void ValidateChar(char c, string original)
    {
        var valid = (c is >= 'A' and <= 'Z') || (c is >= '0' and <= '9') || ValidExtraChars.Contains(c);
        if (!valid)
            throw new ArgumentException($"Caractere inválido '{c}' em nome de arquivo DOS: '{original}'.", nameof(original));
    }

    /// <summary>Monta os 11 bytes (nome+ext, preenchidos com espaço) usados na entrada de diretório.</summary>
    public static byte[] ToDirectoryBytes(string fileName)
    {
        var (name8, ext3) = Split(fileName);
        var bytes = new byte[11];
        Array.Fill(bytes, (byte)' ');
        Encoding.ASCII.GetBytes(name8, 0, name8.Length, bytes, 0);
        Encoding.ASCII.GetBytes(ext3, 0, ext3.Length, bytes, 8);
        return bytes;
    }

    /// <summary>Reconstrói "SONG1.MID" (ou "SONG1" sem extensão) a partir dos 11 bytes da entrada.</summary>
    public static string FromDirectoryBytes(ReadOnlySpan<byte> entryNameBytes)
    {
        var name = Encoding.ASCII.GetString(entryNameBytes[..8]).TrimEnd(' ');
        var ext = Encoding.ASCII.GetString(entryNameBytes[8..11]).TrimEnd(' ');
        return ext.Length == 0 ? name : $"{name}.{ext}";
    }
}
