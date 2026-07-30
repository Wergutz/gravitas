using YamahaFloppy.Core;

namespace YamahaFloppy.App.Models;

/// <summary>
/// Uma linha da lista de disquetes virtuais na tela principal. Guarda o slot
/// (o arquivo .IMG em si) mais quanto espaço já está ocupado por arquivos
/// gravados dentro dele — que é diferente do tamanho do arquivo .IMG no
/// pendrive (esse é sempre fixo: 1.474.560 bytes para 1.44MB, esteja o
/// disquete vazio ou cheio).
/// </summary>
public sealed record FloppySlotDisplay(FloppySlot Slot, int FileCount, long UsedBytes)
{
    public string DisplayName => Slot.DisplayName;
    public string FileName => Slot.FileName;
}
