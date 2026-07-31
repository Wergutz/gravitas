using System.Text;
using YamahaFloppy.Core;
using Xunit;

namespace YamahaFloppy.Core.Tests;

public class Fat12ImageTests
{
    [Theory]
    [InlineData(FloppyFormat.Hd1440, 1_474_560)]
    [InlineData(FloppyFormat.Dd720, 737_280)]
    public void CreateNew_ProducesImageWithExpectedSizeAndBootSignature(FloppyFormat format, int expectedSize)
    {
        var image = Fat12Image.CreateNew(format);
        var bytes = image.GetBytes();

        Assert.Equal(expectedSize, bytes.Length);
        Assert.Equal(0x55, bytes[510]);
        Assert.Equal(0xAA, bytes[511]);
        Assert.Empty(image.ListFiles());
    }

    [Fact]
    public void CreateNew_WritesYamahaOemIdAndIdentificationString()
    {
        // Confirmado byte a byte contra um disquete real formatado por um
        // Yamaha PSR-550 (ver Fat12Image.WriteBootSector): sem esse OEM ID e
        // esse texto, o teclado não reconhece o conteúdo do disquete, mesmo
        // com a estrutura FAT12 correta.
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var bytes = image.GetBytes();

        Assert.Equal("YAMAHA  ", Encoding.ASCII.GetString(bytes, 3, 8));
        Assert.Equal(
            "PSR-550         Ver.1.00        Copyright(C)     1999 by YAMAHA ",
            Encoding.ASCII.GetString(bytes, 96, 64));
    }

    [Fact]
    public void CreateNew_WritesMediaDescriptorMatchingFormat()
    {
        var hd = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var dd = Fat12Image.CreateNew(FloppyFormat.Dd720);

        // O media descriptor fica no offset 21 do boot sector e também
        // espelhado no primeiro byte de cada cópia da FAT.
        Assert.Equal(0xF0, hd.GetBytes()[21]);
        Assert.Equal(0xF9, dd.GetBytes()[21]);
    }

    [Fact]
    public void AddFile_ThenListFiles_ShowsFileWithCorrectMetadata()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var content = Encoding.ASCII.GetBytes("Hello Yamaha!");

        image.AddFile("SONG1.MID", content, new DateTime(2024, 5, 17, 10, 30, 0));

        var files = image.ListFiles();
        Assert.Single(files);
        Assert.Equal("SONG1.MID", files[0].Name);
        Assert.Equal(content.Length, files[0].SizeBytes);
        Assert.Equal(new DateTime(2024, 5, 17, 10, 30, 0), files[0].Timestamp);
    }

    [Fact]
    public void AddFile_ThenExtractFile_RoundTripsBytesExactly()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var content = Encoding.ASCII.GetBytes("Conteudo de teste do disquete virtual.");

        image.AddFile("DATA.BIN", content);
        var extracted = image.ExtractFile("DATA.BIN");

        Assert.Equal(content, extracted);
    }

    [Fact]
    public void AddFile_LargerThanOneCluster_RoundTripsAcrossMultipleClusters()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440); // 1 setor/cluster = 512 bytes/cluster
        var random = new Random(42);
        var content = new byte[512 * 5 + 123]; // força alocação de 6 clusters encadeados
        random.NextBytes(content);

        image.AddFile("BIGFILE.BIN", content);
        var extracted = image.ExtractFile("BIGFILE.BIN");

        Assert.Equal(content, extracted);
    }

    [Fact]
    public void AddFile_EmptyContent_RoundTripsAsZeroLength()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);

        image.AddFile("EMPTY.TXT", Array.Empty<byte>());
        var extracted = image.ExtractFile("EMPTY.TXT");

        Assert.Empty(extracted);
        Assert.Equal(0, image.ListFiles().Single().SizeBytes);
    }

    [Fact]
    public void AddFile_DuplicateNameWithoutOverwrite_Throws()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("SONG1.MID", new byte[10]);

        Assert.Throws<InvalidOperationException>(() => image.AddFile("SONG1.MID", new byte[20]));
    }

    [Fact]
    public void AddFile_DuplicateNameWithOverwrite_ReplacesContent()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("SONG1.MID", Encoding.ASCII.GetBytes("old"));

        image.AddFile("SONG1.MID", Encoding.ASCII.GetBytes("new content"), overwrite: true);

        Assert.Equal("new content", Encoding.ASCII.GetString(image.ExtractFile("SONG1.MID")));
        Assert.Single(image.ListFiles());
    }

    [Fact]
    public void DeleteFile_RemovesFromListingAndFreesSpace()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var freeBefore = image.FreeSpaceBytes;

        image.AddFile("SONG1.MID", new byte[2048]);
        Assert.True(image.FreeSpaceBytes < freeBefore);

        image.DeleteFile("SONG1.MID");

        Assert.Empty(image.ListFiles());
        Assert.Equal(freeBefore, image.FreeSpaceBytes);
    }

    [Fact]
    public void DeleteFile_ThenAddNewFile_ReusesFreedSpace()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("A.BIN", new byte[1024]);
        image.DeleteFile("A.BIN");

        image.AddFile("B.BIN", new byte[1024]);

        Assert.Single(image.ListFiles());
        Assert.Equal("B.BIN", image.ListFiles()[0].Name);
    }

    [Fact]
    public void ExtractFile_UnknownName_ThrowsFileNotFound()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        Assert.Throws<FileNotFoundException>(() => image.ExtractFile("NOPE.TXT"));
    }

    [Fact]
    public void DeleteFile_UnknownName_ThrowsFileNotFound()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        Assert.Throws<FileNotFoundException>(() => image.DeleteFile("NOPE.TXT"));
    }

    [Theory]
    [InlineData("NOMEMUITOGRANDE.TXT")]
    [InlineData("OK.EXTGRANDE")]
    [InlineData("BAD*NAME.TXT")]
    public void AddFile_InvalidDosName_Throws(string invalidName)
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        Assert.Throws<ArgumentException>(() => image.AddFile(invalidName, new byte[10]));
    }

    [Fact]
    public void AddFile_WhenDiskFull_ThrowsIOException()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var capacity = image.TotalCapacityBytes;

        Assert.Throws<IOException>(() => image.AddFile("HUGE.BIN", new byte[capacity * 2]));
    }

    [Fact]
    public void AddFile_WhenRootDirectoryFull_ThrowsIOException()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440); // 224 entradas no diretório raiz

        for (var i = 0; i < 224; i++)
            image.AddFile($"F{i}.BIN", Array.Empty<byte>());

        Assert.Throws<IOException>(() => image.AddFile("ONEMORE.BIN", Array.Empty<byte>()));
    }

    [Fact]
    public void SaveAndLoad_RoundTripsImageIdentically()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("SONG1.MID", Encoding.ASCII.GetBytes("conteudo"));

        var tempPath = Path.Combine(Path.GetTempPath(), Path.GetRandomFileName() + ".img");
        try
        {
            image.Save(tempPath);
            var reloaded = Fat12Image.Load(File.ReadAllBytes(tempPath));

            Assert.Equal(image.GetBytes(), reloaded.GetBytes());
            Assert.Equal("conteudo", Encoding.ASCII.GetString(reloaded.ExtractFile("SONG1.MID")));
        }
        finally
        {
            File.Delete(tempPath);
        }
    }

    [Fact]
    public void Load_WrongSize_ThrowsArgumentException()
    {
        Assert.Throws<ArgumentException>(() => Fat12Image.Load(new byte[1000]));
    }

    [Fact]
    public void Dd720_HasExpectedGeometryAndCapacity()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Dd720);
        Assert.Equal(737_280, image.TotalCapacityBytes);
        Assert.True(image.FreeSpaceBytes > 700_000);
    }

    // ---- Testes das operações de leitura/escrita por offset (base do sistema de
    // arquivos passthrough usado pelo Dokan para montar um disquete virtual no Explorer) ----

    [Fact]
    public void FindFile_ExistingFile_ReturnsMetadata()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("SONG1.MID", new byte[100]);

        var entry = image.FindFile("SONG1.MID");

        Assert.NotNull(entry);
        Assert.Equal("SONG1.MID", entry!.Name);
        Assert.Equal(100, entry.SizeBytes);
    }

    [Fact]
    public void FindFile_UnknownFile_ReturnsNull()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        Assert.Null(image.FindFile("NOPE.TXT"));
    }

    [Fact]
    public void ReadFileData_PartialRangeInMiddle_ReturnsExpectedBytes()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var content = Encoding.ASCII.GetBytes("0123456789ABCDEF");
        image.AddFile("DATA.BIN", content);

        var buffer = new byte[4];
        var read = image.ReadFileData("DATA.BIN", 3, buffer, 0, 4);

        Assert.Equal(4, read);
        Assert.Equal("3456", Encoding.ASCII.GetString(buffer));
    }

    [Fact]
    public void ReadFileData_OffsetPastEndOfFile_ReturnsZero()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", new byte[10]);

        var buffer = new byte[4];
        var read = image.ReadFileData("DATA.BIN", 100, buffer, 0, 4);

        Assert.Equal(0, read);
    }

    [Fact]
    public void ReadFileData_CountBeyondEndOfFile_ReturnsOnlyAvailableBytes()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var content = Encoding.ASCII.GetBytes("Hello");
        image.AddFile("DATA.BIN", content);

        var buffer = new byte[20];
        var read = image.ReadFileData("DATA.BIN", 2, buffer, 0, 20);

        Assert.Equal(3, read); // "llo"
        Assert.Equal("llo", Encoding.ASCII.GetString(buffer, 0, read));
    }

    [Fact]
    public void ReadFileData_AcrossClusterBoundary_ReturnsContiguousBytes()
    {
        // Hd1440: 1 setor/cluster = 512 bytes/cluster.
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var content = new byte[1200];
        new Random(1).NextBytes(content);
        image.AddFile("BIG.BIN", content);

        var buffer = new byte[300];
        var read = image.ReadFileData("BIG.BIN", 400, buffer, 0, 300); // cruza o limite do cluster (512)

        Assert.Equal(300, read);
        Assert.Equal(content[400..700], buffer);
    }

    [Fact]
    public void WriteFileData_OverwritesWithinExistingSize_KeepsSizeUnchanged()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", Encoding.ASCII.GetBytes("0123456789"));

        var patch = Encoding.ASCII.GetBytes("XY");
        image.WriteFileData("DATA.BIN", 3, patch, 0, 2);

        Assert.Equal("012XY56789", Encoding.ASCII.GetString(image.ExtractFile("DATA.BIN")));
        Assert.Equal(10, image.FindFile("DATA.BIN")!.SizeBytes);
    }

    [Fact]
    public void WriteFileData_PastCurrentEnd_ExtendsFileAndAllocatesClusters()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", Encoding.ASCII.GetBytes("abc"));
        var freeBefore = image.FreeSpaceBytes;

        var suffix = Encoding.ASCII.GetBytes("XYZ");
        image.WriteFileData("DATA.BIN", 3, suffix, 0, 3);

        Assert.Equal("abcXYZ", Encoding.ASCII.GetString(image.ExtractFile("DATA.BIN")));
        Assert.Equal(6, image.FindFile("DATA.BIN")!.SizeBytes);
        Assert.True(image.FreeSpaceBytes <= freeBefore);
    }

    [Fact]
    public void WriteFileData_InMultipleChunksAcrossClusters_RoundTripsExactly()
    {
        // Simula o Explorer escrevendo um arquivo grande em pedaços, em qualquer ordem de offset.
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("BIG.BIN", Array.Empty<byte>());

        var content = new byte[1500];
        new Random(7).NextBytes(content);

        const int chunkSize = 400;
        for (var offset = 0; offset < content.Length; offset += chunkSize)
        {
            var count = Math.Min(chunkSize, content.Length - offset);
            image.WriteFileData("BIG.BIN", offset, content, offset, count);
        }

        Assert.Equal(content, image.ExtractFile("BIG.BIN"));
    }

    [Fact]
    public void SetFileLength_Shrink_TruncatesContentAndFreesClusters()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", new byte[2000]); // várias clusters (512 bytes cada)
        var freeBefore = image.FreeSpaceBytes;

        image.SetFileLength("DATA.BIN", 50);

        Assert.Equal(50, image.FindFile("DATA.BIN")!.SizeBytes);
        Assert.Equal(50, image.ExtractFile("DATA.BIN").Length);
        Assert.True(image.FreeSpaceBytes > freeBefore);
    }

    [Fact]
    public void SetFileLength_ShrinkToZero_FreesAllClusters()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", new byte[2000]);
        var freeEmpty = Fat12Image.CreateNew(FloppyFormat.Hd1440).FreeSpaceBytes;

        image.SetFileLength("DATA.BIN", 0);

        Assert.Equal(0, image.FindFile("DATA.BIN")!.SizeBytes);
        Assert.Equal(freeEmpty, image.FreeSpaceBytes);
    }

    [Fact]
    public void SetFileLength_Grow_ExtendsFileWithAdditionalClusters()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("DATA.BIN", Encoding.ASCII.GetBytes("abc"));

        image.SetFileLength("DATA.BIN", 1000);

        Assert.Equal(1000, image.FindFile("DATA.BIN")!.SizeBytes);
        Assert.Equal(1000, image.ExtractFile("DATA.BIN").Length);
    }

    [Fact]
    public void RenameFile_ExistingFile_KeepsContentUnderNewName()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("OLD.TXT", Encoding.ASCII.GetBytes("conteudo"));

        image.RenameFile("OLD.TXT", "NEW.TXT");

        Assert.False(image.ContainsFile("OLD.TXT"));
        Assert.True(image.ContainsFile("NEW.TXT"));
        Assert.Equal("conteudo", Encoding.ASCII.GetString(image.ExtractFile("NEW.TXT")));
    }

    [Fact]
    public void RenameFile_TargetNameAlreadyExists_Throws()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        image.AddFile("A.TXT", new byte[1]);
        image.AddFile("B.TXT", new byte[1]);

        Assert.Throws<InvalidOperationException>(() => image.RenameFile("A.TXT", "B.TXT"));
    }

    [Fact]
    public void RenameFile_UnknownFile_ThrowsFileNotFound()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        Assert.Throws<FileNotFoundException>(() => image.RenameFile("NOPE.TXT", "NEW.TXT"));
    }
}
