using System.Runtime.InteropServices;

namespace YamahaFloppy.App.Services;

/// <summary>
/// Verifica se o driver do Dokan (dokan2.dll) está instalado antes de tentar montar um
/// disquete virtual como unidade — sem isso, o erro só apareceria como uma
/// <see cref="DllNotFoundException"/> confusa vindo de dentro do DokanNet, ou pior,
/// falhando silenciosamente feito o serviço legado da ipcas que motivou essa reescrita.
/// </summary>
public static class DokanAvailability
{
    private const string DokanNativeLibrary = "dokan2.dll";

    public static bool IsInstalled()
    {
        if (!NativeLibrary.TryLoad(DokanNativeLibrary, out var handle))
            return false;

        NativeLibrary.Free(handle);
        return true;
    }
}
