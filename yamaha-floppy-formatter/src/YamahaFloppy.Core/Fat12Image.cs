using System.Text;

namespace YamahaFloppy.Core;

/// <summary>
/// Representa, inteiramente em memória, uma imagem de disquete virtual FAT12
/// (720KB ou 1.44MB) compatível com os emuladores de disquete Gotek/FlashFloppy
/// usados em teclados Yamaha. A imagem é um "disquete cru": os mesmos bytes que
/// existiriam em um disquete físico formatado, sem tabela de partições MBR.
///
/// Cada instância mantém o disquete inteiro em um único byte[] em memória
/// (no máximo 1.44MB), o que é suficiente e simples para esse caso de uso.
/// </summary>
public sealed class Fat12Image
{
    private const ushort EndOfChain = 0xFFF;
    private const ushort FreeCluster = 0x000;
    private const int DirEntrySize = 32;
    private const byte DirEntryFree = 0x00;
    private const byte DirEntryDeleted = 0xE5;
    private const byte AttrReadOnly = 0x01;
    private const byte AttrHidden = 0x02;
    private const byte AttrVolumeLabel = 0x08;
    private const byte AttrLongName = 0x0F;
    private const byte AttrArchive = 0x20;

    private readonly byte[] _data;

    public FloppyFormat Format { get; }
    public FloppyGeometry Geometry { get; }

    private Fat12Image(FloppyFormat format, byte[] data)
    {
        Format = format;
        Geometry = FloppyGeometry.For(format);
        _data = data;
    }

    public int TotalCapacityBytes => Geometry.TotalBytes;

    public int FreeSpaceBytes
    {
        get
        {
            var free = 0;
            for (var cluster = 2; cluster < Geometry.ClusterCount + 2; cluster++)
                if (GetFatEntry(cluster) == FreeCluster)
                    free += Geometry.BytesPerCluster;
            return free;
        }
    }

    /// <summary>Cria uma imagem de disquete virtual em branco, já formatada em FAT12.</summary>
    public static Fat12Image CreateNew(FloppyFormat format)
    {
        var geometry = FloppyGeometry.For(format);
        var data = new byte[geometry.TotalBytes];

        WriteBootSector(data, geometry);

        // Inicializa as duas cópias da FAT: cluster 0 guarda o media descriptor,
        // cluster 1 é marcado como fim-de-cadeia (ambos reservados pelo padrão FAT).
        for (var fatIndex = 0; fatIndex < geometry.NumberOfFats; fatIndex++)
        {
            var fatOffset = (geometry.FirstFatSector + fatIndex * geometry.SectorsPerFat) * FloppyGeometry.SectorSize;
            data[fatOffset] = geometry.MediaDescriptor;
            data[fatOffset + 1] = 0xFF;
            data[fatOffset + 2] = 0xFF;
        }

        return new Fat12Image(format, data);
    }

    /// <summary>Carrega uma imagem existente a partir dos bytes crus do disquete.</summary>
    public static Fat12Image Load(byte[] data)
    {
        var format = data.Length switch
        {
            1_474_560 => FloppyFormat.Hd1440,
            737_280 => FloppyFormat.Dd720,
            _ => throw new ArgumentException($"Tamanho de imagem não corresponde a um disquete conhecido: {data.Length} bytes."),
        };

        if (data[510] != 0x55 || data[511] != 0xAA)
            throw new ArgumentException("Assinatura de boot sector (0x55AA) ausente: imagem inválida ou corrompida.");

        return new Fat12Image(format, (byte[])data.Clone());
    }

    public static Fat12Image Load(Stream stream)
    {
        using var ms = new MemoryStream();
        stream.CopyTo(ms);
        return Load(ms.ToArray());
    }

    /// <summary>Retorna uma cópia dos bytes crus da imagem (para gravar em arquivo .img).</summary>
    public byte[] GetBytes() => (byte[])_data.Clone();

    public void Save(Stream stream) => stream.Write(_data, 0, _data.Length);

    public void Save(string path)
    {
        using var fs = File.Create(path);
        Save(fs);
    }

    /// <summary>Lista os arquivos presentes no diretório raiz (nomes 8.3, sem subpastas).</summary>
    public IReadOnlyList<FloppyDirectoryEntry> ListFiles()
    {
        var result = new List<FloppyDirectoryEntry>();
        var rootOffset = Geometry.FirstRootDirSector * FloppyGeometry.SectorSize;

        for (var i = 0; i < Geometry.RootEntryCount; i++)
        {
            var entryOffset = rootOffset + i * DirEntrySize;
            var firstByte = _data[entryOffset];
            if (firstByte is DirEntryFree or DirEntryDeleted)
                continue;

            var attr = _data[entryOffset + 11];
            if ((attr & AttrLongName) == AttrLongName || (attr & AttrVolumeLabel) != 0)
                continue;

            result.Add(ReadDirectoryEntry(entryOffset));
        }

        return result;
    }

    /// <summary>Busca metadados de um único arquivo pelo nome, ou null se não existir.</summary>
    public FloppyDirectoryEntry? FindFile(string fileName)
    {
        var entryOffset = FindEntryOffset(FatShortName.ToDirectoryBytes(fileName));
        return entryOffset is null ? null : ReadDirectoryEntry(entryOffset.Value);
    }

    /// <summary>Copia um arquivo do PC para dentro do disquete virtual (na raiz).</summary>
    public void AddFile(string fileName, byte[] content, DateTime? timestamp = null, bool overwrite = false)
    {
        var nameBytes = FatShortName.ToDirectoryBytes(fileName);

        var existingOffset = FindEntryOffset(nameBytes);
        if (existingOffset is not null)
        {
            if (!overwrite)
                throw new InvalidOperationException($"O disquete virtual já contém um arquivo chamado '{fileName}'.");
            DeleteFile(fileName);
        }

        var clusterSize = Geometry.BytesPerCluster;
        var clustersNeeded = content.Length == 0 ? 0 : (content.Length + clusterSize - 1) / clusterSize;

        var freeSlot = FindFreeDirectoryEntryOffset();
        if (freeSlot is null)
            throw new IOException($"Diretório raiz do disquete virtual está cheio (máximo de {Geometry.RootEntryCount} arquivos).");

        var clusters = AllocateClusters(clustersNeeded);
        WriteClusterChain(clusters, content);

        var firstCluster = clusters.Count > 0 ? clusters[0] : 0;
        WriteDirectoryEntry(freeSlot.Value, nameBytes, firstCluster, content.Length, timestamp ?? DateTime.Now);
    }

    /// <summary>Lê o conteúdo de um arquivo do disquete virtual, para copiar para o PC.</summary>
    public byte[] ExtractFile(string fileName)
    {
        var entry = FindFile(fileName)
            ?? throw new FileNotFoundException($"Arquivo '{fileName}' não encontrado no disquete virtual.");

        if (entry.SizeBytes == 0)
            return Array.Empty<byte>();

        var result = new byte[entry.SizeBytes];
        ReadFileData(fileName, 0, result, 0, entry.SizeBytes);
        return result;
    }

    /// <summary>
    /// Lê até <paramref name="count"/> bytes do arquivo a partir de <paramref name="fileOffset"/>,
    /// gravando em <paramref name="destination"/> a partir de <paramref name="destinationOffset"/>.
    /// Retorna quantos bytes foram efetivamente lidos (0 se o offset já passou do fim do
    /// arquivo). Usado pelo sistema de arquivos passthrough (Dokan), onde o Explorer lê em
    /// pedaços e em qualquer posição.
    /// </summary>
    public int ReadFileData(string fileName, long fileOffset, byte[] destination, int destinationOffset, int count)
    {
        var nameBytes = FatShortName.ToDirectoryBytes(fileName);
        var entryOffset = FindEntryOffset(nameBytes)
            ?? throw new FileNotFoundException($"Arquivo '{fileName}' não encontrado no disquete virtual.");

        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        var firstCluster = BitConverter.ToUInt16(entry[26..28]);
        var size = BitConverter.ToInt32(entry[28..32]);

        if (fileOffset >= size || count == 0)
            return 0;

        var toRead = (int)Math.Min(count, size - fileOffset);
        var clusters = GetClusterChain(firstCluster);
        var clusterSize = Geometry.BytesPerCluster;
        var read = 0;

        while (read < toRead)
        {
            var absoluteOffset = fileOffset + read;
            var clusterIndex = (int)(absoluteOffset / clusterSize);
            var clusterInnerOffset = (int)(absoluteOffset % clusterSize);
            var chunk = Math.Min(toRead - read, clusterSize - clusterInnerOffset);
            var byteOffset = ClusterToByteOffset(clusters[clusterIndex]) + clusterInnerOffset;
            _data.AsSpan(byteOffset, chunk).CopyTo(destination.AsSpan(destinationOffset + read, chunk));
            read += chunk;
        }

        return read;
    }

    /// <summary>
    /// Escreve <paramref name="count"/> bytes de <paramref name="source"/> no arquivo a partir
    /// de <paramref name="fileOffset"/>, alocando mais clusters se a escrita for além do
    /// tamanho atual. Usado pelo sistema de arquivos passthrough (Dokan), onde o Explorer
    /// escreve em pedaços e pode escrever além do fim atual do arquivo.
    /// </summary>
    public void WriteFileData(string fileName, long fileOffset, byte[] source, int sourceOffset, int count)
    {
        var nameBytes = FatShortName.ToDirectoryBytes(fileName);
        var entryOffset = FindEntryOffset(nameBytes)
            ?? throw new FileNotFoundException($"Arquivo '{fileName}' não encontrado no disquete virtual.");

        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        int firstCluster = BitConverter.ToUInt16(entry[26..28]);
        var size = BitConverter.ToInt32(entry[28..32]);

        if (count == 0)
            return;

        var endOffset = fileOffset + count;
        var clusters = GetClusterChain(firstCluster);
        firstCluster = GrowClusterChain(firstCluster, clusters, endOffset);

        var clusterSize = Geometry.BytesPerCluster;
        var written = 0;
        while (written < count)
        {
            var absoluteOffset = fileOffset + written;
            var clusterIndex = (int)(absoluteOffset / clusterSize);
            var clusterInnerOffset = (int)(absoluteOffset % clusterSize);
            var chunk = Math.Min(count - written, clusterSize - clusterInnerOffset);
            var byteOffset = ClusterToByteOffset(clusters[clusterIndex]) + clusterInnerOffset;
            source.AsSpan(sourceOffset + written, chunk).CopyTo(_data.AsSpan(byteOffset, chunk));
            written += chunk;
        }

        var newSize = (int)Math.Max(size, endOffset);
        BitConverter.GetBytes((ushort)firstCluster).CopyTo(entry[26..28]);
        BitConverter.GetBytes(newSize).CopyTo(entry[28..32]);
    }

    /// <summary>
    /// Ajusta o tamanho do arquivo para exatamente <paramref name="newLength"/> bytes,
    /// alocando ou liberando clusters conforme necessário. Usado pelas chamadas
    /// SetEndOfFile/SetAllocationSize do sistema de arquivos passthrough (Dokan).
    /// </summary>
    public void SetFileLength(string fileName, long newLength)
    {
        var nameBytes = FatShortName.ToDirectoryBytes(fileName);
        var entryOffset = FindEntryOffset(nameBytes)
            ?? throw new FileNotFoundException($"Arquivo '{fileName}' não encontrado no disquete virtual.");

        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        int firstCluster = BitConverter.ToUInt16(entry[26..28]);
        var clusters = GetClusterChain(firstCluster);
        var clusterSize = Geometry.BytesPerCluster;
        var neededClusters = newLength <= 0 ? 0 : (int)((newLength + clusterSize - 1) / clusterSize);

        if (neededClusters < clusters.Count)
        {
            for (var i = neededClusters; i < clusters.Count; i++)
                SetFatEntry(clusters[i], FreeCluster);

            if (neededClusters == 0)
                firstCluster = 0;
            else
                SetFatEntry(clusters[neededClusters - 1], EndOfChain);
        }
        else if (neededClusters > clusters.Count)
        {
            firstCluster = GrowClusterChain(firstCluster, clusters, newLength);
        }

        BitConverter.GetBytes((ushort)firstCluster).CopyTo(entry[26..28]);
        BitConverter.GetBytes((int)newLength).CopyTo(entry[28..32]);
    }

    /// <summary>Renomeia um arquivo já existente no diretório raiz, mantendo seu conteúdo.</summary>
    public void RenameFile(string oldFileName, string newFileName)
    {
        var oldNameBytes = FatShortName.ToDirectoryBytes(oldFileName);
        var entryOffset = FindEntryOffset(oldNameBytes)
            ?? throw new FileNotFoundException($"Arquivo '{oldFileName}' não encontrado no disquete virtual.");

        if (ContainsFile(newFileName))
            throw new InvalidOperationException($"O disquete virtual já contém um arquivo chamado '{newFileName}'.");

        var newNameBytes = FatShortName.ToDirectoryBytes(newFileName);
        newNameBytes.CopyTo(_data, entryOffset);
    }

    /// <summary>Remove um arquivo do disquete virtual, liberando seus clusters.</summary>
    public void DeleteFile(string fileName)
    {
        var nameBytes = FatShortName.ToDirectoryBytes(fileName);
        var entryOffset = FindEntryOffset(nameBytes)
            ?? throw new FileNotFoundException($"Arquivo '{fileName}' não encontrado no disquete virtual.");

        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        var firstCluster = BitConverter.ToUInt16(entry[26..28]);

        foreach (var cluster in GetClusterChain(firstCluster))
            SetFatEntry(cluster, FreeCluster);

        _data[entryOffset] = DirEntryDeleted;
    }

    public bool ContainsFile(string fileName) => FindEntryOffset(FatShortName.ToDirectoryBytes(fileName)) is not null;

    // ---- Implementação interna: FAT12, diretório raiz, clusters ----

    private int? FindEntryOffset(byte[] nameBytes)
    {
        var rootOffset = Geometry.FirstRootDirSector * FloppyGeometry.SectorSize;
        for (var i = 0; i < Geometry.RootEntryCount; i++)
        {
            var entryOffset = rootOffset + i * DirEntrySize;
            var firstByte = _data[entryOffset];
            if (firstByte is DirEntryFree or DirEntryDeleted)
                continue;

            if (_data.AsSpan(entryOffset, 11).SequenceEqual(nameBytes))
                return entryOffset;
        }

        return null;
    }

    private int? FindFreeDirectoryEntryOffset()
    {
        var rootOffset = Geometry.FirstRootDirSector * FloppyGeometry.SectorSize;
        for (var i = 0; i < Geometry.RootEntryCount; i++)
        {
            var entryOffset = rootOffset + i * DirEntrySize;
            if (_data[entryOffset] is DirEntryFree or DirEntryDeleted)
                return entryOffset;
        }

        return null;
    }

    private FloppyDirectoryEntry ReadDirectoryEntry(int entryOffset)
    {
        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        var attr = entry[11];
        var name = FatShortName.FromDirectoryBytes(entry[..11]);
        var firstCluster = BitConverter.ToUInt16(entry[26..28]);
        var size = BitConverter.ToInt32(entry[28..32]);
        var writeTime = BitConverter.ToUInt16(entry[22..24]);
        var writeDate = BitConverter.ToUInt16(entry[24..26]);

        return new FloppyDirectoryEntry(
            name,
            size,
            firstCluster,
            DecodeDosDateTime(writeDate, writeTime),
            IsReadOnly: (attr & AttrReadOnly) != 0,
            IsHidden: (attr & AttrHidden) != 0);
    }

    /// <summary>Percorre a cadeia de clusters de um arquivo a partir do primeiro cluster.</summary>
    private List<int> GetClusterChain(int firstCluster)
    {
        var clusters = new List<int>();
        var cluster = firstCluster;
        while (cluster != 0 && cluster < EndOfChain - 7)
        {
            clusters.Add(cluster);
            cluster = GetFatEntry(cluster);
        }
        return clusters;
    }

    /// <summary>
    /// Garante que <paramref name="clusters"/> tenha clusters suficientes para cobrir até
    /// <paramref name="endOffset"/> bytes, alocando e encadeando clusters adicionais se
    /// necessário. Retorna o primeiro cluster (que muda se a lista começava vazia).
    /// </summary>
    private int GrowClusterChain(int firstCluster, List<int> clusters, long endOffset)
    {
        var clusterSize = Geometry.BytesPerCluster;
        var neededClusters = endOffset <= 0 ? 0 : (int)((endOffset + clusterSize - 1) / clusterSize);

        if (neededClusters <= clusters.Count)
            return firstCluster;

        var additional = AllocateClusters(neededClusters - clusters.Count);
        if (clusters.Count == 0)
            firstCluster = additional[0];
        else
            SetFatEntry(clusters[^1], (ushort)additional[0]);

        clusters.AddRange(additional);
        return firstCluster;
    }

    private void WriteDirectoryEntry(int entryOffset, byte[] nameBytes, int firstCluster, int size, DateTime timestamp)
    {
        var entry = _data.AsSpan(entryOffset, DirEntrySize);
        entry.Clear();
        nameBytes.CopyTo(entry[..11]);
        entry[11] = AttrArchive;

        var (dosDate, dosTime) = EncodeDosDateTime(timestamp);
        BitConverter.GetBytes(dosTime).CopyTo(entry[14..16]); // criação
        BitConverter.GetBytes(dosDate).CopyTo(entry[16..18]);
        BitConverter.GetBytes(dosDate).CopyTo(entry[18..20]); // último acesso
        BitConverter.GetBytes(dosTime).CopyTo(entry[22..24]); // gravação
        BitConverter.GetBytes(dosDate).CopyTo(entry[24..26]);
        BitConverter.GetBytes((ushort)firstCluster).CopyTo(entry[26..28]);
        BitConverter.GetBytes(size).CopyTo(entry[28..32]);
    }

    private List<int> AllocateClusters(int count)
    {
        var clusters = new List<int>(count);
        if (count == 0)
            return clusters;

        for (var cluster = 2; cluster < Geometry.ClusterCount + 2 && clusters.Count < count; cluster++)
            if (GetFatEntry(cluster) == FreeCluster)
                clusters.Add(cluster);

        if (clusters.Count < count)
            throw new IOException("Espaço insuficiente no disquete virtual.");

        for (var i = 0; i < clusters.Count; i++)
        {
            var value = i == clusters.Count - 1 ? EndOfChain : (ushort)clusters[i + 1];
            SetFatEntry(clusters[i], value);
        }

        return clusters;
    }

    private void WriteClusterChain(List<int> clusters, byte[] content)
    {
        var clusterSize = Geometry.BytesPerCluster;
        var written = 0;
        foreach (var cluster in clusters)
        {
            var toCopy = Math.Min(clusterSize, content.Length - written);
            var clusterOffset = ClusterToByteOffset(cluster);
            content.AsSpan(written, toCopy).CopyTo(_data.AsSpan(clusterOffset, toCopy));
            written += toCopy;
        }
    }

    private int ClusterToByteOffset(int cluster)
    {
        var sector = Geometry.FirstDataSector + (cluster - 2) * Geometry.SectorsPerCluster;
        return sector * FloppyGeometry.SectorSize;
    }

    private ushort GetFatEntry(int cluster)
    {
        var fatOffset = Geometry.FirstFatSector * FloppyGeometry.SectorSize;
        var byteOffset = fatOffset + cluster + cluster / 2;

        if (cluster % 2 == 0)
            return (ushort)(((_data[byteOffset + 1] & 0x0F) << 8) | _data[byteOffset]);

        return (ushort)((_data[byteOffset + 1] << 4) | (_data[byteOffset] >> 4));
    }

    private void SetFatEntry(int cluster, ushort value)
    {
        value = (ushort)(value & 0x0FFF);

        for (var fatIndex = 0; fatIndex < Geometry.NumberOfFats; fatIndex++)
        {
            var fatOffset = (Geometry.FirstFatSector + fatIndex * Geometry.SectorsPerFat) * FloppyGeometry.SectorSize;
            var byteOffset = fatOffset + cluster + cluster / 2;

            if (cluster % 2 == 0)
            {
                _data[byteOffset] = (byte)(value & 0xFF);
                _data[byteOffset + 1] = (byte)((_data[byteOffset + 1] & 0xF0) | ((value >> 8) & 0x0F));
            }
            else
            {
                _data[byteOffset] = (byte)((_data[byteOffset] & 0x0F) | ((value & 0x0F) << 4));
                _data[byteOffset + 1] = (byte)((value >> 4) & 0xFF);
            }
        }
    }

    /// <summary>
    /// Texto de identificação gravado a partir do offset 96 (0x60) do boot sector,
    /// copiado byte a byte de um disquete real formatado por um Yamaha PSR-550. O
    /// firmware do teclado (e do emulador Gotek em modo original) valida o OEM ID
    /// e esse texto antes de aceitar o disquete como "seu" — sem eles, o disquete é
    /// listado (o firmware enxerga a FAT12 normalmente) mas o conteúdo não é
    /// reconhecido, que era exatamente o sintoma relatado.
    /// </summary>
    private const string YamahaIdentificationText =
        "PSR-550         Ver.1.00        Copyright(C)     1999 by YAMAHA ";

    private static void WriteBootSector(byte[] data, FloppyGeometry geometry)
    {
        // JMP curto + NOP, presente em todo boot sector FAT válido. O destino do
        // salto (0x1C) é o mesmo usado pelo disquete real do PSR-550, replicado
        // aqui por completude — o firmware não executa esse código, só valida a
        // estrutura da BPB e o texto de identificação abaixo.
        data[0] = 0xEB;
        data[1] = 0x1C;
        data[2] = 0x90;

        // OEM ID: o teclado só reconhece disquetes com esse identificador exato
        // (confirmado comparando byte a byte com um disquete formatado por ele).
        Encoding.ASCII.GetBytes("YAMAHA  ").CopyTo(data, 3);

        BitConverter.GetBytes((ushort)FloppyGeometry.SectorSize).CopyTo(data, 11);
        data[13] = (byte)geometry.SectorsPerCluster;
        BitConverter.GetBytes((ushort)geometry.ReservedSectors).CopyTo(data, 14);
        data[16] = (byte)geometry.NumberOfFats;
        BitConverter.GetBytes((ushort)geometry.RootEntryCount).CopyTo(data, 17);
        BitConverter.GetBytes((ushort)geometry.TotalSectors).CopyTo(data, 19);
        data[21] = geometry.MediaDescriptor;
        BitConverter.GetBytes((ushort)geometry.SectorsPerFat).CopyTo(data, 22);
        BitConverter.GetBytes((ushort)geometry.SectorsPerTrack).CopyTo(data, 24);
        BitConverter.GetBytes((ushort)geometry.NumberOfHeads).CopyTo(data, 26);
        BitConverter.GetBytes((uint)0).CopyTo(data, 28); // hidden sectors
        BitConverter.GetBytes((uint)0).CopyTo(data, 32); // total sectors (32-bit, não usado aqui)

        // Bytes 36-95 (número de drive, assinatura de boot estendida, serial de
        // volume, rótulo e tipo de FS) ficam zerados: o disquete real do PSR-550
        // não usa a BPB estendida do MS-DOS, então preenchê-la (como um disquete
        // "genérico" faria) só adiciona bytes que o teclado não espera encontrar.

        Encoding.ASCII.GetBytes(YamahaIdentificationText).CopyTo(data, 96);

        data[510] = 0x55;
        data[511] = 0xAA;
    }

    private static (ushort DosDate, ushort DosTime) EncodeDosDateTime(DateTime dt)
    {
        var year = Math.Clamp(dt.Year - 1980, 0, 127);
        var dosDate = (ushort)((year << 9) | (dt.Month << 5) | dt.Day);
        var dosTime = (ushort)((dt.Hour << 11) | (dt.Minute << 5) | (dt.Second / 2));
        return (dosDate, dosTime);
    }

    private static DateTime DecodeDosDateTime(ushort dosDate, ushort dosTime)
    {
        var year = 1980 + ((dosDate >> 9) & 0x7F);
        var month = Math.Clamp((dosDate >> 5) & 0x0F, 1, 12);
        var day = Math.Clamp(dosDate & 0x1F, 1, DateTime.DaysInMonth(year, month));
        var hour = (dosTime >> 11) & 0x1F;
        var minute = (dosTime >> 5) & 0x3F;
        var second = (dosTime & 0x1F) * 2;

        try
        {
            return new DateTime(year, month, day, hour, minute, Math.Min(second, 59));
        }
        catch (ArgumentOutOfRangeException)
        {
            return DateTime.Now;
        }
    }
}
