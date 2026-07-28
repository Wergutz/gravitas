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
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440, "TESTDISK");
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
}
