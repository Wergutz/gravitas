using System.Security.Principal;
using DokanNet;
using YamahaFloppy.Core;
using Xunit;
using FileAccess = DokanNet.FileAccess;

namespace YamahaFloppy.Core.Tests;

/// <summary>
/// Stub mínimo de <see cref="IDokanFileInfo"/>. O <c>MockDokanFileInfo</c> que vem com o
/// próprio DokanNet não serve aqui porque seu construtor chama
/// <see cref="WindowsIdentity.GetCurrent"/>, que lança <see cref="PlatformNotSupportedException"/>
/// fora do Windows — e queremos rodar estes testes no Linux também.
/// </summary>
internal sealed class FakeDokanFileInfo : IDokanFileInfo
{
    public object? Context { get; set; }
    public bool DeletePending { get; set; }
    public bool IsDirectory { get; set; }
    public bool NoCache => false;
    public bool PagingIo => false;
    public int ProcessId => 0;
    public bool SynchronousIo => false;
    public bool WriteToEndOfFile { get; set; }

    public WindowsIdentity GetRequestor() => null!;
    public bool TryResetTimeout(int milliseconds) => true;
}

/// <summary>
/// Testa <see cref="FloppyDokanOperations"/> chamando a interface do Dokan diretamente
/// (com <see cref="FakeDokanFileInfo"/> no lugar do contexto real), simulando o que o
/// Explorer faria ao montar um disquete virtual como unidade — sem precisar do driver
/// do Dokan instalado, então roda em qualquer plataforma.
/// </summary>
public class FloppyDokanOperationsTests
{
    private static FloppyDokanOperations CreateOperations(out Fat12Image image)
    {
        image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        return new FloppyDokanOperations(image);
    }

    [Fact]
    public void CreateFile_OnRoot_MarksAsDirectory()
    {
        var ops = CreateOperations(out _);
        var info = new FakeDokanFileInfo();

        var status = ops.CreateFile("\\", FileAccess.ReadData, FileShare.ReadWrite, FileMode.Open,
            FileOptions.None, FileAttributes.Normal, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.True(info.IsDirectory);
    }

    [Fact]
    public void CreateFile_ThenWriteThenRead_RoundTripsContent()
    {
        var ops = CreateOperations(out _);
        var info = new FakeDokanFileInfo();

        var createStatus = ops.CreateFile("\\SONG1.MID", FileAccess.WriteData, FileShare.ReadWrite,
            FileMode.CreateNew, FileOptions.None, FileAttributes.Normal, info);
        Assert.Equal(DokanResult.Success, createStatus);

        var content = "conteudo de teste"u8.ToArray();
        var writeStatus = ops.WriteFile("\\SONG1.MID", content, out var written, 0, info);
        Assert.Equal(DokanResult.Success, writeStatus);
        Assert.Equal(content.Length, written);

        var buffer = new byte[content.Length];
        var readStatus = ops.ReadFile("\\SONG1.MID", buffer, out var read, 0, info);

        Assert.Equal(DokanResult.Success, readStatus);
        Assert.Equal(content.Length, read);
        Assert.Equal(content, buffer);
    }

    [Fact]
    public void CreateFile_ExistingWithCreateNew_ReturnsFileExists()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("SONG1.MID", Array.Empty<byte>());
        var info = new FakeDokanFileInfo();

        var status = ops.CreateFile("\\SONG1.MID", FileAccess.WriteData, FileShare.ReadWrite,
            FileMode.CreateNew, FileOptions.None, FileAttributes.Normal, info);

        Assert.Equal(DokanResult.FileExists, status);
    }

    [Fact]
    public void CreateFile_MissingWithOpenMode_ReturnsFileNotFound()
    {
        var ops = CreateOperations(out _);
        var info = new FakeDokanFileInfo();

        var status = ops.CreateFile("\\NOPE.TXT", FileAccess.ReadData, FileShare.ReadWrite,
            FileMode.Open, FileOptions.None, FileAttributes.Normal, info);

        Assert.Equal(DokanResult.FileNotFound, status);
    }

    [Fact]
    public void WriteFile_AtWriteToEndOfFile_AppendsIgnoringOffsetParameter()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("LOG.TXT", "abc"u8.ToArray());
        var info = new FakeDokanFileInfo { WriteToEndOfFile = true };

        var suffix = "def"u8.ToArray();
        var status = ops.WriteFile("\\LOG.TXT", suffix, out var written, offset: 0, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal(3, written);
        Assert.Equal("abcdef", System.Text.Encoding.ASCII.GetString(image.ExtractFile("LOG.TXT")));
    }

    [Fact]
    public void FindFiles_ListsFilesAddedDirectlyToImage()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("A.TXT", new byte[10]);
        image.AddFile("B.TXT", new byte[20]);
        var info = new FakeDokanFileInfo();

        var status = ops.FindFiles("\\", out var files, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal(2, files.Count);
        Assert.Contains(files, f => f.FileName == "A.TXT" && f.Length == 10);
        Assert.Contains(files, f => f.FileName == "B.TXT" && f.Length == 20);
    }

    [Fact]
    public void FindFilesWithPattern_FiltersByWildcard()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("SONG1.MID", new byte[1]);
        image.AddFile("SONG2.MID", new byte[1]);
        image.AddFile("STYLE1.STY", new byte[1]);
        var info = new FakeDokanFileInfo();

        var status = ops.FindFilesWithPattern("\\", "*.MID", out var files, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal(2, files.Count);
        Assert.All(files, f => Assert.EndsWith(".MID", f.FileName));
    }

    [Fact]
    public void GetFileInformation_ExistingFile_ReturnsSizeAndAttributes()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("DATA.BIN", new byte[42]);
        var info = new FakeDokanFileInfo();

        var status = ops.GetFileInformation("\\DATA.BIN", out var fileInfo, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal("DATA.BIN", fileInfo.FileName);
        Assert.Equal(42, fileInfo.Length);
    }

    [Fact]
    public void GetFileInformation_UnknownFile_ReturnsFileNotFound()
    {
        var ops = CreateOperations(out _);
        var info = new FakeDokanFileInfo();

        var status = ops.GetFileInformation("\\NOPE.TXT", out _, info);

        Assert.Equal(DokanResult.FileNotFound, status);
    }

    [Fact]
    public void Cleanup_WithDeletePending_RemovesFileFromImage()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("TEMP.TXT", new byte[5]);
        var info = new FakeDokanFileInfo { DeletePending = true };

        ops.Cleanup("\\TEMP.TXT", info);

        Assert.False(image.ContainsFile("TEMP.TXT"));
    }

    [Fact]
    public void Cleanup_WithoutDeletePending_KeepsFile()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("KEEP.TXT", new byte[5]);
        var info = new FakeDokanFileInfo { DeletePending = false };

        ops.Cleanup("\\KEEP.TXT", info);

        Assert.True(image.ContainsFile("KEEP.TXT"));
    }

    [Fact]
    public void MoveFile_RenamesFileInImage()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("OLD.TXT", "conteudo"u8.ToArray());
        var info = new FakeDokanFileInfo();

        var status = ops.MoveFile("\\OLD.TXT", "\\NEW.TXT", replace: false, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.False(image.ContainsFile("OLD.TXT"));
        Assert.True(image.ContainsFile("NEW.TXT"));
    }

    [Fact]
    public void SetEndOfFile_ShrinksFile()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("DATA.BIN", new byte[1000]);
        var info = new FakeDokanFileInfo();

        var status = ops.SetEndOfFile("\\DATA.BIN", 10, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal(10, image.FindFile("DATA.BIN")!.SizeBytes);
    }

    [Fact]
    public void GetDiskFreeSpace_ReflectsImageCapacityAndUsage()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("DATA.BIN", new byte[2000]);
        var info = new FakeDokanFileInfo();

        var status = ops.GetDiskFreeSpace(out var free, out var total, out var totalFree, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal(image.TotalCapacityBytes, total);
        Assert.Equal(image.FreeSpaceBytes, free);
        Assert.Equal(image.FreeSpaceBytes, totalFree);
        Assert.True(free < total);
    }

    [Fact]
    public void ReadFile_UnknownFile_ReturnsFileNotFoundViaExceptionMapping()
    {
        // Diferente de CreateFile_MissingWithOpenMode (que retorna FileNotFound direto),
        // aqui o FileNotFoundException vem de dentro do Fat12Image e passa pelo
        // catch/DokanHelper.ToNtStatus — confirma que esse caminho funciona de verdade
        // (não só compila) fora do Windows.
        var ops = CreateOperations(out _);
        var info = new FakeDokanFileInfo();
        var buffer = new byte[10];

        var status = ops.ReadFile("\\NOPE.TXT", buffer, out var read, 0, info);

        Assert.Equal(DokanResult.FileNotFound, status);
        Assert.Equal(0, read);
    }

    [Fact]
    public void MoveFile_TargetAlreadyExistsWithoutReplace_ReturnsFileExistsViaExceptionMapping()
    {
        var ops = CreateOperations(out var image);
        image.AddFile("A.TXT", new byte[1]);
        image.AddFile("B.TXT", new byte[1]);
        var info = new FakeDokanFileInfo();

        var status = ops.MoveFile("\\A.TXT", "\\B.TXT", replace: false, info);

        Assert.Equal(DokanResult.FileExists, status);
    }

    [Fact]
    public void GetVolumeInformation_ReturnsFat12AndGivenLabel()
    {
        var image = Fat12Image.CreateNew(FloppyFormat.Hd1440);
        var ops = new FloppyDokanOperations(image, volumeLabel: "MEUDISCO");
        var info = new FakeDokanFileInfo();

        var status = ops.GetVolumeInformation(out var label, out _, out var fsName, out _, info);

        Assert.Equal(DokanResult.Success, status);
        Assert.Equal("MEUDISCO", label);
        Assert.Equal("FAT12", fsName);
    }
}
