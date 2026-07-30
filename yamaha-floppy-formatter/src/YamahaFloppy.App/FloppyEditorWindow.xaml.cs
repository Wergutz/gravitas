using System.IO;
using System.Windows;
using Microsoft.Win32;
using YamahaFloppy.Core;
using MessageBox = System.Windows.MessageBox;

namespace YamahaFloppy.App;

public partial class FloppyEditorWindow : Window
{
    private readonly FloppySlot _slot;
    private Fat12Image _image;

    public FloppyEditorWindow(FloppySlot slot)
    {
        InitializeComponent();
        _slot = slot;
        _image = EmulatorVolume.OpenDisk(slot);

        TitleTextBlock.Text = $"{slot.DisplayName} — {slot.FileName}";
        Refresh();
    }

    private void Refresh()
    {
        FilesListView.ItemsSource = _image.ListFiles();
        FooterTextBlock.Text =
            $"Espaço livre: {_image.FreeSpaceBytes:N0} bytes de {_image.TotalCapacityBytes:N0} bytes.";
    }

    private void ImportButton_Click(object sender, RoutedEventArgs e)
    {
        var dialog = new Microsoft.Win32.OpenFileDialog
        {
            Title = "Selecione arquivo(s) para copiar para o disquete virtual",
            Multiselect = true,
        };

        if (dialog.ShowDialog(this) != true)
            return;

        foreach (var path in dialog.FileNames)
        {
            try
            {
                var fileName = Path.GetFileName(path);
                var content = File.ReadAllBytes(path);

                if (_image.ContainsFile(fileName))
                {
                    var overwrite = MessageBox.Show(this,
                        $"O disquete virtual já tem um arquivo chamado '{fileName}'. Substituir?",
                        "Arquivo já existe", MessageBoxButton.YesNo, MessageBoxImage.Question);
                    if (overwrite != MessageBoxResult.Yes)
                        continue;
                }

                _image.AddFile(fileName, content, overwrite: true);
            }
            catch (Exception ex)
            {
                MessageBox.Show(this, $"Não foi possível importar '{Path.GetFileName(path)}':\n{ex.Message}",
                    "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }

        SaveAndRefresh();
    }

    private void ExportButton_Click(object sender, RoutedEventArgs e)
    {
        if (FilesListView.SelectedItem is not FloppyDirectoryEntry entry)
        {
            MessageBox.Show(this, "Selecione um arquivo na lista para exportar.", "Aviso",
                MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        var dialog = new Microsoft.Win32.SaveFileDialog
        {
            Title = "Salvar arquivo do disquete virtual",
            FileName = entry.Name,
        };

        if (dialog.ShowDialog(this) != true)
            return;

        try
        {
            var content = _image.ExtractFile(entry.Name);
            File.WriteAllBytes(dialog.FileName, content);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Não foi possível exportar '{entry.Name}':\n{ex.Message}",
                "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void DeleteButton_Click(object sender, RoutedEventArgs e)
    {
        if (FilesListView.SelectedItem is not FloppyDirectoryEntry entry)
        {
            MessageBox.Show(this, "Selecione um arquivo na lista para apagar.", "Aviso",
                MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        var confirm = MessageBox.Show(this, $"Apagar '{entry.Name}' deste disquete virtual?",
            "Confirmar exclusão", MessageBoxButton.YesNo, MessageBoxImage.Warning, MessageBoxResult.No);
        if (confirm != MessageBoxResult.Yes)
            return;

        try
        {
            _image.DeleteFile(entry.Name);
            SaveAndRefresh();
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Não foi possível apagar '{entry.Name}':\n{ex.Message}",
                "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
        }
    }

    private void SaveAndRefresh()
    {
        try
        {
            EmulatorVolume.SaveDisk(_slot, _image);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Não foi possível salvar as alterações no disquete virtual:\n{ex.Message}",
                "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
        }

        Refresh();
    }
}
