<?php
/**
 * Instalador de Windows: icono de escritorio que abre la app en linea.
 */

class EscritorioController {
    public function descargar() {
        $icono = BASE_DIR . '/front/public/img/icono.ico';
        if (!is_file($icono)) {
            http_response_code(404);
            echo 'No esta el icono de la aplicacion.';
            exit;
        }

        $bat = $this->instalador(BASE_URL, $icono);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="Instalar Nunca Jamas.bat"');
        header('Content-Length: ' . (string) strlen($bat));
        header('Cache-Control: no-store');
        echo $bat;
        exit;
    }

    private function instalador($url, $icono) {
        $url = str_replace("'", "''", $url);
        $script = <<<'PS'
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Windows.Forms
function Aviso($texto) {
    [System.Windows.Forms.MessageBox]::Show($texto, 'Nunca Jamas') | Out-Null
}
$texto = [IO.File]::ReadAllText($env:NJ_SELF)
$marca = $texto.LastIndexOf(":ICONO")
if ($marca -lt 0) {
    Aviso 'El instalador esta incompleto. Descargalo otra vez desde la aplicacion.'
    exit 1
}
$b64 = ($texto.Substring($marca + 6) -replace '\s', '')
$dest = $env:LOCALAPPDATA + '\NuncaJamas'
New-Item -ItemType Directory -Force -Path $dest | Out-Null
$icono = $dest + '\icono.ico'
[IO.File]::WriteAllBytes($icono, [Convert]::FromBase64String($b64))
$navegador = $null
$bases = @($env:ProgramFiles, [Environment]::GetEnvironmentVariable('ProgramFiles(x86)'))
$relativos = @('Microsoft\Edge\Application\msedge.exe', 'Google\Chrome\Application\chrome.exe')
foreach ($base in $bases) {
    if (-not $base) { continue }
    foreach ($rel in $relativos) {
        $ruta = Join-Path $base $rel
        if (Test-Path -LiteralPath $ruta) {
            $navegador = $ruta
            break
        }
    }
    if ($navegador) { break }
}
if (-not $navegador) {
    Aviso 'Este PC no tiene Microsoft Edge ni Chrome. Instala Edge y vuelve a ejecutar el instalador.'
    exit 1
}
$url = '__URL__'
$shell = New-Object -ComObject WScript.Shell
$atajos = @(
    (Join-Path ([Environment]::GetFolderPath('Desktop')) 'Nunca Jamas.lnk'),
    (Join-Path ([Environment]::GetFolderPath('Programs')) 'Nunca Jamas.lnk')
)
foreach ($ruta in $atajos) {
    $acceso = $shell.CreateShortcut($ruta)
    $acceso.TargetPath = $navegador
    $acceso.Arguments = '--app="' + $url + '" --start-maximized'
    $acceso.IconLocation = $icono
    $acceso.Description = 'Nunca Jamas'
    $acceso.Save()
}
Aviso 'El icono Nunca Jamas quedo en el escritorio y en el menu Inicio. Cada vez que lo abras veras la version que esta en linea.'
PS;
        $script = str_replace('__URL__', $url, $script);
        $codificado = base64_encode(iconv('UTF-8', 'UTF-16LE', $script));
        $datos = chunk_split(base64_encode((string) file_get_contents($icono)), 76, "\r\n");
        return "@echo off\r\n"
            . "set \"NJ_SELF=%~f0\"\r\n"
            . "powershell.exe -NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -EncodedCommand " . $codificado . "\r\n"
            . "exit /b %ERRORLEVEL%\r\n"
            . ":ICONO\r\n"
            . $datos;
    }
}
