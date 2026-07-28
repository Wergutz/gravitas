namespace YamahaFloppy.App.Models;

/// <summary>
/// Informações de um disco físico removível conectado por USB, coletadas via WMI.
/// Guardamos o PNPDeviceID (identificador estável do hardware) além do número de
/// disco, porque o número de disco pode ser reatribuído pelo Windows se outro
/// pendrive for conectado/removido entre a listagem e a formatação.
/// </summary>
public sealed record UsbDriveInfo(
    int DiskNumber,
    string PnpDeviceId,
    string Model,
    long SizeBytes,
    string InterfaceType,
    IReadOnlyList<string> DriveLetters)
{
    public string SizeDisplay => SizeBytes >= 1_000_000_000
        ? $"{SizeBytes / 1_000_000_000.0:0.0} GB"
        : $"{SizeBytes / 1_000_000.0:0.0} MB";

    public string DriveLettersDisplay => DriveLetters.Count == 0
        ? "(sem letra atribuída)"
        : string.Join(", ", DriveLetters);

    public override string ToString() => $"Disco {DiskNumber}: {Model} — {SizeDisplay} — {DriveLettersDisplay}";
}
