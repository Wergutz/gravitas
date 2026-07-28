using YamahaFloppy.Core;
using Xunit;

namespace YamahaFloppy.Core.Tests;

public class EmulatorVolumeTests : IDisposable
{
    private readonly string _tempDir = Path.Combine(Path.GetTempPath(), "yamaha-floppy-tests-" + Path.GetRandomFileName());

    public EmulatorVolumeTests() => Directory.CreateDirectory(_tempDir);

    public void Dispose()
    {
        if (Directory.Exists(_tempDir))
            Directory.Delete(_tempDir, recursive: true);
    }

    [Theory]
    [InlineData(0, "DSKA0000.IMG")]
    [InlineData(7, "DSKA0007.IMG")]
    [InlineData(9999, "DSKA9999.IMG")]
    public void BuildFileName_FormatsIndexWithFourDigits(int index, string expected)
    {
        Assert.Equal(expected, EmulatorVolume.BuildFileName(index));
    }

    [Fact]
    public void BuildFileName_IndexOutOfRange_Throws()
    {
        Assert.Throws<ArgumentOutOfRangeException>(() => EmulatorVolume.BuildFileName(10000));
        Assert.Throws<ArgumentOutOfRangeException>(() => EmulatorVolume.BuildFileName(-1));
    }

    [Theory]
    [InlineData("DSKA0000.IMG", true, 0)]
    [InlineData("dska0042.img", true, 42)]
    [InlineData("DSKA0000.HFE", false, -1)]
    [InlineData("SOMETHING.IMG", false, -1)]
    [InlineData("DSKA00.IMG", false, -1)]
    public void TryParseIndex_ParsesOnlyValidNames(string fileName, bool expectedOk, int expectedIndex)
    {
        var ok = EmulatorVolume.TryParseIndex(fileName, out var index);
        Assert.Equal(expectedOk, ok);
        if (expectedOk)
            Assert.Equal(expectedIndex, index);
    }

    [Fact]
    public void CreateDisks_CreatesExpectedNumberOfFilesWithCorrectSize()
    {
        var slots = EmulatorVolume.CreateDisks(_tempDir, count: 5, FloppyFormat.Hd1440);

        Assert.Equal(5, slots.Count);
        Assert.Equal(new[] { 0, 1, 2, 3, 4 }, slots.Select(s => s.Index));
        foreach (var slot in slots)
        {
            Assert.True(File.Exists(slot.FilePath));
            Assert.Equal(1_474_560, new FileInfo(slot.FilePath).Length);
        }
    }

    [Fact]
    public void CreateDisks_RespectsStartIndex()
    {
        EmulatorVolume.CreateDisks(_tempDir, count: 2, FloppyFormat.Hd1440, startIndex: 0);
        var moreSlots = EmulatorVolume.CreateDisks(_tempDir, count: 3, FloppyFormat.Hd1440, startIndex: 2);

        Assert.Equal(new[] { 2, 3, 4 }, moreSlots.Select(s => s.Index));

        var all = EmulatorVolume.ListSlots(_tempDir);
        Assert.Equal(5, all.Count);
    }

    [Fact]
    public void CreateDisks_DoesNotOverwriteExistingSlot()
    {
        EmulatorVolume.CreateDisks(_tempDir, count: 1, FloppyFormat.Hd1440);
        Assert.Throws<IOException>(() => EmulatorVolume.CreateDisks(_tempDir, count: 1, FloppyFormat.Hd1440));
    }

    [Fact]
    public void ListSlots_ReturnsSortedByIndexAndIgnoresUnrelatedFiles()
    {
        EmulatorVolume.CreateDisks(_tempDir, count: 3, FloppyFormat.Dd720);
        File.WriteAllText(Path.Combine(_tempDir, "leiame.txt"), "não é um disquete");

        var slots = EmulatorVolume.ListSlots(_tempDir);

        Assert.Equal(3, slots.Count);
        Assert.Equal(new[] { 0, 1, 2 }, slots.Select(s => s.Index));
        Assert.All(slots, s => Assert.Equal(FloppyFormat.Dd720, s.Format));
    }

    [Fact]
    public void ListSlots_NonExistentFolder_ReturnsEmpty()
    {
        var slots = EmulatorVolume.ListSlots(Path.Combine(_tempDir, "nao-existe"));
        Assert.Empty(slots);
    }

    [Fact]
    public void OpenDisk_ThenAddFile_ThenSaveDisk_PersistsChanges()
    {
        var slots = EmulatorVolume.CreateDisks(_tempDir, count: 1, FloppyFormat.Hd1440);
        var slot = slots[0];

        var image = EmulatorVolume.OpenDisk(slot);
        image.AddFile("SONG1.MID", new byte[] { 1, 2, 3, 4 });
        EmulatorVolume.SaveDisk(slot, image);

        var reloaded = EmulatorVolume.OpenDisk(slot);
        Assert.Single(reloaded.ListFiles());
        Assert.Equal(new byte[] { 1, 2, 3, 4 }, reloaded.ExtractFile("SONG1.MID"));
    }

    [Fact]
    public void DisplayName_ReflectsFormat()
    {
        var slot = new FloppySlot(3, "x.img", 1_474_560, FloppyFormat.Hd1440);
        Assert.Equal("Disco 03 (1.44MB)", slot.DisplayName);
    }
}
