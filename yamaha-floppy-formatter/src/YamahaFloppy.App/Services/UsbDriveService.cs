using System.Diagnostics;
using System.IO;
using System.Management;
using YamahaFloppy.App.Models;

namespace YamahaFloppy.App.Services;

/// <summary>
/// Detecta pendrives USB e os formata com uma única partição FAT32, usando
/// diskpart. Isso é o suficiente e o correto para os emuladores de disquete
/// estilo Gotek/FlashFloppy: eles leem múltiplos arquivos de imagem de uma
/// única partição, não múltiplas partições reais (ver <c>EmulatorVolume</c>
/// em YamahaFloppy.Core, que cria os arquivos DSKA0000.IMG etc. dentro dessa
/// partição).
///
/// Esta classe só existe no projeto Windows (net8.0-windows) porque depende
/// de WMI e do utilitário diskpart.exe, ambos exclusivos do Windows.
/// </summary>
public static class UsbDriveService
{
    /// <summary>Lista discos físicos conectados por USB (nunca discos internos/fixos).</summary>
    public static IReadOnlyList<UsbDriveInfo> ListUsbDrives()
    {
        var drives = new List<UsbDriveInfo>();

        using var searcher = new ManagementObjectSearcher("SELECT * FROM Win32_DiskDrive WHERE InterfaceType='USB'");
        foreach (ManagementObject disk in searcher.Get())
        {
            var deviceId = (disk["DeviceID"] as string) ?? string.Empty;
            if (deviceId.Length == 0)
                continue;

            var diskNumber = disk["Index"] is null ? -1 : Convert.ToInt32(disk["Index"]);
            var model = (disk["Model"] as string) ?? "Dispositivo desconhecido";
            var size = disk["Size"] is null ? 0L : Convert.ToInt64(disk["Size"]);
            var pnpId = (disk["PNPDeviceID"] as string) ?? deviceId;
            var interfaceType = (disk["InterfaceType"] as string) ?? "USB";

            var letters = GetDriveLettersForDisk(deviceId);
            drives.Add(new UsbDriveInfo(diskNumber, pnpId, model, size, interfaceType, letters));
        }

        return drives;
    }

    /// <summary>
    /// Reconsulta o WMI por identificador estável de hardware (PNPDeviceID),
    /// para confirmar, imediatamente antes de formatar, que o pendrive
    /// escolhido pelo usuário ainda é o mesmo dispositivo físico — o número
    /// do disco pode mudar se outro pendrive for conectado/removido entre a
    /// listagem inicial e o clique em "Formatar".
    /// </summary>
    public static UsbDriveInfo? FindByPnpDeviceId(string pnpDeviceId) =>
        ListUsbDrives().FirstOrDefault(d => d.PnpDeviceId == pnpDeviceId);

    /// <summary>
    /// Apaga todo o conteúdo do pendrive e cria uma única partição FAT32.
    /// Retorna a letra de unidade atribuída (ex.: "E:\").
    /// </summary>
    public static async Task<string> FormatDriveAsync(UsbDriveInfo drive, string volumeLabel, CancellationToken ct = default)
    {
        var current = FindByPnpDeviceId(drive.PnpDeviceId)
            ?? throw new InvalidOperationException(
                "O pendrive selecionado não foi encontrado. Ele pode ter sido desconectado — operação cancelada por segurança.");

        if (!current.InterfaceType.Equals("USB", StringComparison.OrdinalIgnoreCase))
            throw new InvalidOperationException("Por segurança, só é permitido formatar discos conectados via USB.");

        var driveLetter = GetFirstAvailableDriveLetter();
        var cleanLabel = SanitizeVolumeLabel(volumeLabel);

        var script =
            $"select disk {current.DiskNumber}\r\n" +
            "clean\r\n" +
            "create partition primary\r\n" +
            $"format fs=fat32 quick label=\"{cleanLabel}\"\r\n" +
            $"assign letter={driveLetter}\r\n";

        var (exitCode, output) = await RunDiskpartScriptAsync(script, ct);
        if (exitCode != 0)
        {
            throw new InvalidOperationException(
                $"diskpart retornou código {exitCode} ao formatar o disco {current.DiskNumber}. Saída:\n{output}");
        }

        return $"{driveLetter}:\\";
    }

    private static List<string> GetDriveLettersForDisk(string diskDeviceId)
    {
        var letters = new List<string>();

        using var partitionSearcher = new ManagementObjectSearcher(
            $"ASSOCIATORS OF {{Win32_DiskDrive.DeviceID='{EscapeForWql(diskDeviceId)}'}} WHERE AssocClass = Win32_DiskDriveToDiskPartition");

        foreach (ManagementObject partition in partitionSearcher.Get())
        {
            var partitionId = (partition["DeviceID"] as string) ?? string.Empty;
            if (partitionId.Length == 0)
                continue;

            using var logicalSearcher = new ManagementObjectSearcher(
                $"ASSOCIATORS OF {{Win32_DiskPartition.DeviceID='{EscapeForWql(partitionId)}'}} WHERE AssocClass = Win32_LogicalDiskToPartition");

            foreach (ManagementObject logicalDisk in logicalSearcher.Get())
            {
                var letter = logicalDisk["DeviceID"] as string;
                if (!string.IsNullOrEmpty(letter))
                    letters.Add(letter);
            }
        }

        return letters;
    }

    private static string EscapeForWql(string value) => value.Replace(@"\", @"\\").Replace("'", @"\'");

    private static char GetFirstAvailableDriveLetter()
    {
        var used = DriveInfo.GetDrives()
            .Select(d => char.ToUpperInvariant(d.Name[0]))
            .ToHashSet();

        for (var c = 'D'; c <= 'Z'; c++)
            if (!used.Contains(c))
                return c;

        throw new InvalidOperationException("Não há letras de unidade livres no Windows para atribuir ao pendrive formatado.");
    }

    /// <summary>
    /// Remove qualquer caractere que não seja letra/dígito/espaço, para (a) respeitar
    /// as regras de rótulo de volume FAT32 e (b) impedir que texto digitado pelo
    /// usuário quebre o script do diskpart (ex.: aspas fechando o parâmetro antes da hora).
    /// </summary>
    private static string SanitizeVolumeLabel(string label)
    {
        var cleaned = new string(label.Where(c => char.IsLetterOrDigit(c) || c == ' ').ToArray())
            .ToUpperInvariant()
            .Trim();

        if (cleaned.Length == 0)
            cleaned = "YAMAHA";

        return cleaned.Length > 11 ? cleaned[..11] : cleaned;
    }

    private static async Task<(int ExitCode, string Output)> RunDiskpartScriptAsync(string scriptContent, CancellationToken ct)
    {
        var scriptPath = Path.Combine(Path.GetTempPath(), $"yamaha-floppy-{Guid.NewGuid():N}.txt");
        await File.WriteAllTextAsync(scriptPath, scriptContent, ct);

        try
        {
            var psi = new ProcessStartInfo
            {
                FileName = "diskpart.exe",
                Arguments = $"/s \"{scriptPath}\"",
                RedirectStandardOutput = true,
                RedirectStandardError = true,
                UseShellExecute = false,
                CreateNoWindow = true,
            };

            using var process = Process.Start(psi)
                ?? throw new InvalidOperationException("Não foi possível iniciar o diskpart.exe.");

            var stdOutTask = process.StandardOutput.ReadToEndAsync(ct);
            var stdErrTask = process.StandardError.ReadToEndAsync(ct);
            await process.WaitForExitAsync(ct);

            var output = await stdOutTask + Environment.NewLine + await stdErrTask;
            return (process.ExitCode, output);
        }
        finally
        {
            File.Delete(scriptPath);
        }
    }
}
