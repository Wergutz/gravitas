using DokanNet;
using DokanNet.Logging;
using YamahaFloppy.Core;

namespace YamahaFloppy.App.Services;

/// <summary>Um disquete virtual atualmente montado como unidade real do Windows.</summary>
public sealed class MountedFloppy
{
    public required char DriveLetter { get; init; }
    public required FloppySlot Slot { get; init; }
    public required Fat12Image Image { get; init; }
    public required DokanInstance Instance { get; init; }
}

/// <summary>
/// Monta disquetes virtuais (arquivos .IMG) como unidades reais do Windows via Dokan, pra
/// poder arrastar/soltar arquivos neles direto pelo Explorer, em vez de usar os botões
/// Importar/Exportar do editor. Cada unidade montada mantém o <see cref="Fat12Image"/>
/// correspondente em memória; "Desmontar" grava as mudanças de volta no .IMG antes de
/// liberar a letra de unidade.
///
/// Precisa do driver do Dokan instalado na máquina (ver <c>DokanAvailability</c>) — sem
/// ele, o construtor de <see cref="Dokan"/> ou o <c>Build</c> abaixo lançam
/// <see cref="DllNotFoundException"/>, que o chamador deve tratar.
/// </summary>
public sealed class FloppyMountService : IDisposable
{
    private readonly Dictionary<string, MountedFloppy> _mounted = new(StringComparer.OrdinalIgnoreCase);
    private readonly Lazy<Dokan> _dokan = new(() => new Dokan(new NullLogger()));

    public IReadOnlyCollection<MountedFloppy> MountedFloppies => _mounted.Values;

    public char? GetMountedDriveLetter(string filePath) =>
        _mounted.TryGetValue(filePath, out var mounted) ? mounted.DriveLetter : null;

    /// <summary>Monta o disquete virtual em uma letra de unidade livre e retorna essa letra.</summary>
    public char Mount(FloppySlot slot)
    {
        if (_mounted.ContainsKey(slot.FilePath))
            throw new InvalidOperationException($"'{slot.FileName}' já está montado.");

        var driveLetter = GetFirstAvailableDriveLetter();
        var image = EmulatorVolume.OpenDisk(slot);
        var volumeLabel = System.IO.Path.GetFileNameWithoutExtension(slot.FileName).ToUpperInvariant();
        var operations = new FloppyDokanOperations(image, volumeLabel);

        var instance = new DokanInstanceBuilder(_dokan.Value)
            .ConfigureOptions(options =>
            {
                options.MountPoint = $"{driveLetter}:\\";
                options.Options = DokanOptions.RemovableDrive;
            })
            .Build(operations);

        _mounted[slot.FilePath] = new MountedFloppy
        {
            DriveLetter = driveLetter,
            Slot = slot,
            Image = image,
            Instance = instance,
        };

        return driveLetter;
    }

    /// <summary>Desmonta a unidade e grava as mudanças de volta no arquivo .IMG.</summary>
    public void Unmount(FloppySlot slot)
    {
        if (!_mounted.Remove(slot.FilePath, out var mounted))
            return;

        _dokan.Value.Unmount(mounted.DriveLetter);
        mounted.Instance.Dispose();

        EmulatorVolume.SaveDisk(mounted.Slot, mounted.Image);
    }

    /// <summary>Desmonta tudo que estiver montado, salvando cada disquete virtual antes.</summary>
    public void UnmountAll()
    {
        foreach (var slot in _mounted.Values.Select(m => m.Slot).ToList())
            Unmount(slot);
    }

    public void Dispose()
    {
        UnmountAll();
        if (_dokan.IsValueCreated)
            _dokan.Value.Dispose();
    }

    private static char GetFirstAvailableDriveLetter()
    {
        var used = DriveInfo.GetDrives()
            .Select(d => char.ToUpperInvariant(d.Name[0]))
            .ToHashSet();

        for (var c = 'D'; c <= 'Z'; c++)
            if (!used.Contains(c))
                return c;

        throw new InvalidOperationException("Não há letras de unidade livres no Windows para montar o disquete virtual.");
    }
}
