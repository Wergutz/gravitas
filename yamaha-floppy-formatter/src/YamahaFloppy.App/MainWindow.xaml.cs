using System.IO;
using System.Linq;
using System.Windows;
using System.Windows.Forms;
using YamahaFloppy.App.Models;
using YamahaFloppy.App.Services;
using YamahaFloppy.Core;
using MessageBox = System.Windows.MessageBox;

namespace YamahaFloppy.App;

public partial class MainWindow : Window
{
    private IReadOnlyList<UsbDriveInfo> _drives = Array.Empty<UsbDriveInfo>();
    private string? _currentFolder;

    public MainWindow()
    {
        InitializeComponent();
        RefreshDrives();
    }

    private void RefreshDrivesButton_Click(object sender, RoutedEventArgs e) => RefreshDrives();

    private void RefreshDrives()
    {
        try
        {
            _drives = UsbDriveService.ListUsbDrives();
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Não foi possível listar dispositivos USB:\n{ex.Message}",
                "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
            _drives = Array.Empty<UsbDriveInfo>();
        }

        DrivesComboBox.ItemsSource = null;
        DrivesComboBox.ItemsSource = _drives;
        if (_drives.Count > 0)
            DrivesComboBox.SelectedIndex = 0;

        SetStatus(_drives.Count == 0
            ? "Nenhum pendrive USB encontrado. Conecte um pendrive e clique em 'Atualizar lista'."
            : $"{_drives.Count} pendrive(s) USB encontrado(s).");
    }

    private void DrivesComboBox_SelectionChanged(object sender, System.Windows.Controls.SelectionChangedEventArgs e)
    {
        // Nada além de deixar o botão Formatar habilitado; a validação real ocorre no clique.
    }

    private async void FormatButton_Click(object sender, RoutedEventArgs e)
    {
        if (DrivesComboBox.SelectedItem is not UsbDriveInfo selectedDrive)
        {
            MessageBox.Show(this, "Selecione um pendrive na lista antes de formatar.", "Aviso",
                MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        if (ConfirmEraseCheckBox.IsChecked != true)
        {
            MessageBox.Show(this, "Marque a caixa confirmando que deseja apagar todos os dados do pendrive.",
                "Aviso", MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        if (!int.TryParse(DiskCountTextBox.Text, out var diskCount) || diskCount <= 0 || diskCount > 999)
        {
            MessageBox.Show(this, "Informe uma quantidade de disquetes válida (entre 1 e 999).", "Aviso",
                MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        var format = FloppyFormatComboBox.SelectedIndex == 1 ? FloppyFormat.Dd720 : FloppyFormat.Hd1440;
        var floppyBytes = format == FloppyFormat.Hd1440 ? 1_474_560 : 737_280;
        var neededBytes = (long)diskCount * floppyBytes;

        if (neededBytes > selectedDrive.SizeBytes)
        {
            MessageBox.Show(this,
                $"{diskCount} disquetes de {(format == FloppyFormat.Hd1440 ? "1.44MB" : "720KB")} não cabem em " +
                $"{selectedDrive.SizeDisplay}. Reduza a quantidade ou escolha um pendrive maior.",
                "Espaço insuficiente", MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        var confirm = MessageBox.Show(this,
            $"ATENÇÃO: isso vai apagar PERMANENTEMENTE todos os dados de:\n\n" +
            $"{selectedDrive}\n\n" +
            $"E criar uma nova partição FAT16 com {diskCount} disquete(s) virtual(is).\n\n" +
            "Esta ação não pode ser desfeita. Deseja continuar?",
            "Confirmar formatação", MessageBoxButton.YesNo, MessageBoxImage.Warning, MessageBoxResult.No);

        if (confirm != MessageBoxResult.Yes)
            return;

        SetBusy(true, "Formatando pendrive (isso pode levar alguns segundos)...");
        try
        {
            var rootPath = await UsbDriveService.FormatDriveAsync(selectedDrive, VolumeLabelTextBox.Text);

            SetStatus("Criando disquetes virtuais...");
            EmulatorVolume.CreateDisks(rootPath, diskCount, format);

            _currentFolder = rootPath;
            LoadSlotsFromCurrentFolder();
            SetStatus($"Pronto! {diskCount} disquete(s) virtual(is) criado(s) em {rootPath}.");
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Falha ao formatar o pendrive:\n{ex.Message}", "Erro",
                MessageBoxButton.OK, MessageBoxImage.Error);
            SetStatus("Falha na formatação.");
        }
        finally
        {
            SetBusy(false, null);
            RefreshDrives();
        }
    }

    private void OpenExistingFolderButton_Click(object sender, RoutedEventArgs e)
    {
        using var dialog = new FolderBrowserDialog
        {
            Description = "Selecione a unidade/pasta raiz do pendrive já preparado (ex.: E:\\)",
            UseDescriptionForTitle = true,
        };

        if (dialog.ShowDialog() != System.Windows.Forms.DialogResult.OK)
            return;

        _currentFolder = dialog.SelectedPath;
        LoadSlotsFromCurrentFolder();
    }

    private void LoadSlotsFromCurrentFolder()
    {
        if (_currentFolder is null)
            return;

        CurrentFolderTextBlock.Text = $"Pasta atual: {_currentFolder}";

        IReadOnlyList<FloppySlot> slots;
        try
        {
            slots = EmulatorVolume.ListSlots(_currentFolder);
        }
        catch (Exception ex)
        {
            MessageBox.Show(this, $"Não foi possível ler os disquetes virtuais desta pasta:\n{ex.Message}",
                "Erro", MessageBoxButton.OK, MessageBoxImage.Error);
            return;
        }

        SlotsListView.ItemsSource = slots.Select(BuildDisplayRow).ToList();

        if (slots.Count == 0)
        {
            SetStatus("Nenhum disquete virtual (DSKA####.IMG) encontrado nesta pasta.");
        }
    }

    /// <summary>
    /// Abre cada disquete para contar quantos arquivos e quantos bytes já
    /// estão gravados nele (diferente do tamanho do .IMG, que é sempre fixo).
    /// </summary>
    private static FloppySlotDisplay BuildDisplayRow(FloppySlot slot)
    {
        try
        {
            var files = EmulatorVolume.OpenDisk(slot).ListFiles();
            return new FloppySlotDisplay(slot, files.Count, files.Sum(f => (long)f.SizeBytes));
        }
        catch
        {
            return new FloppySlotDisplay(slot, 0, 0);
        }
    }

    private void SlotsListView_MouseDoubleClick(object sender, System.Windows.Input.MouseButtonEventArgs e)
    {
        if (SlotsListView.SelectedItem is not FloppySlotDisplay row)
            return;

        var slot = row.Slot;
        if (slot.Format is null)
        {
            MessageBox.Show(this, $"O arquivo '{slot.FileName}' não tem um tamanho de disquete reconhecido (720KB/1.44MB).",
                "Não suportado", MessageBoxButton.OK, MessageBoxImage.Warning);
            return;
        }

        var editor = new FloppyEditorWindow(slot) { Owner = this };
        editor.ShowDialog();

        // Reflete no resumo (Arquivos/Usado) qualquer alteração feita no editor.
        LoadSlotsFromCurrentFolder();
    }

    private void SetBusy(bool busy, string? status)
    {
        DrivesComboBox.IsEnabled = !busy;
        RefreshDrivesButton.IsEnabled = !busy;
        FormatButton.IsEnabled = !busy;
        OpenExistingFolderButton.IsEnabled = !busy;
        DiskCountTextBox.IsEnabled = !busy;
        VolumeLabelTextBox.IsEnabled = !busy;
        FloppyFormatComboBox.IsEnabled = !busy;
        ConfirmEraseCheckBox.IsEnabled = !busy;

        if (status is not null)
            SetStatus(status);
    }

    private void SetStatus(string text) => StatusTextBlock.Text = text;
}
