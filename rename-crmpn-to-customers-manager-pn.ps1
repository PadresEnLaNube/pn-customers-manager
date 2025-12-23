# Script PowerShell para reemplazar 'crmpn' por 'customers-manager-pn' en todos los archivos
# Uso: .\rename-crmpn-to-customers-manager-pn.ps1 -Path "C:\ruta\a\tu\carpeta"

param(
    [Parameter(Mandatory=$false)]
    [string]$Path = (Get-Location).Path,
    
    [Parameter(Mandatory=$false)]
    [switch]$WhatIf = $false,
    
    [Parameter(Mandatory=$false)]
    [switch]$Backup = $true
)

# Extensiones de archivo a procesar (excluye binarios)
$textExtensions = @('.php', '.js', '.css', '.txt', '.md', '.json', '.xml', '.html', '.htm', '.scss', '.sass', '.less', '.sql', '.yml', '.yaml')

# Función para verificar si un archivo es de texto
function Test-TextFile {
    param([string]$FilePath)
    
    $extension = [System.IO.Path]::GetExtension($FilePath).ToLower()
    
    # Si tiene extensión conocida de texto, procesar
    if ($textExtensions -contains $extension) {
        return $true
    }
    
    # Si no tiene extensión, intentar detectar si es texto
    try {
        $content = Get-Content -Path $FilePath -TotalCount 1 -ErrorAction SilentlyContinue
        return $true
    } catch {
        return $false
    }
}

# Función para hacer backup
function Backup-Files {
    param([string]$SourcePath)
    
    $backupPath = Join-Path $SourcePath "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss')"
    
    Write-Host "Creando backup en: $backupPath" -ForegroundColor Yellow
    
    try {
        Copy-Item -Path $SourcePath -Destination $backupPath -Recurse -Force
        Write-Host "Backup creado exitosamente" -ForegroundColor Green
        return $backupPath
    } catch {
        Write-Host "Error al crear backup: $_" -ForegroundColor Red
        return $null
    }
}

# Verificar que la ruta existe
if (-not (Test-Path $Path)) {
    Write-Host "Error: La ruta '$Path' no existe" -ForegroundColor Red
    exit 1
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Reemplazo de prefijos: crmpn -> customers-manager-pn" -ForegroundColor Cyan
Write-Host "Ruta: $Path" -ForegroundColor Cyan
Write-Host "Modo: $(if ($WhatIf) { 'SIMULACIÓN (no se harán cambios)' } else { 'EJECUCIÓN REAL' })" -ForegroundColor $(if ($WhatIf) { 'Yellow' } else { 'Red' })
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Crear backup si se solicita
if ($Backup -and -not $WhatIf) {
    $backupLocation = Backup-Files -SourcePath $Path
    if (-not $backupLocation) {
        $confirm = Read-Host "¿Continuar sin backup? (s/N)"
        if ($confirm -ne 's' -and $confirm -ne 'S') {
            Write-Host "Operación cancelada" -ForegroundColor Yellow
            exit 0
        }
    }
}

# Contadores
$filesProcessed = 0
$filesRenamed = 0
$replacementsCount = 0

# Obtener todos los archivos recursivamente
$allFiles = Get-ChildItem -Path $Path -Recurse -File

Write-Host "Encontrados $($allFiles.Count) archivos para procesar" -ForegroundColor Cyan
Write-Host ""

# Procesar cada archivo
foreach ($file in $allFiles) {
    $filePath = $file.FullName
    
    # Verificar si es archivo de texto
    if (-not (Test-TextFile -FilePath $filePath)) {
        continue
    }
    
    $fileProcessed = $false
    $fileRenamed = $false
    
    # 1. Reemplazar contenido del archivo
    try {
        $content = Get-Content -Path $filePath -Raw -Encoding UTF8 -ErrorAction Stop
        
        if ($content -match 'crmpn') {
            $fileProcessed = $true
            $originalContent = $content
            
            # Reemplazos específicos para diferentes variantes
            $newContent = $content
            
            # Reemplazar 'crmpn-' por 'customers-manager-pn-'
            $newContent = $newContent -replace 'crmpn-', 'customers-manager-pn-'
            
            # Reemplazar 'crmpn_' por 'customers-manager-pn_'
            $newContent = $newContent -replace 'crmpn_', 'customers-manager-pn_'
            
            # Reemplazar 'crmpn' (como palabra completa) por 'customers-manager-pn'
            # Usar lookahead y lookbehind para asegurar que es una palabra completa
            $newContent = $newContent -replace '\bcrmpn\b', 'customers-manager-pn'
            
            # Reemplazar 'CRMPN' (mayúsculas) por 'CUSTOMERS_MANAGER_PN'
            $newContent = $newContent -replace '\bCRMPN\b', 'CUSTOMERS_MANAGER_PN'
            
            # Contar reemplazos
            $replacements = ([regex]::Matches($originalContent, 'crmpn', 'IgnoreCase')).Count
            
            if ($newContent -ne $originalContent) {
                $replacementsCount += $replacements
                
                if (-not $WhatIf) {
                    # Guardar con codificación UTF8 sin BOM
                    [System.IO.File]::WriteAllText($filePath, $newContent, [System.Text.UTF8Encoding]::new($false))
                }
                
                Write-Host "[CONTENIDO] $($file.Name) - $replacements reemplazo(s)" -ForegroundColor Green
            }
        }
    } catch {
        Write-Host "[ERROR] No se pudo procesar contenido de: $($file.Name) - $_" -ForegroundColor Red
    }
    
    # 2. Renombrar archivo si contiene 'crmpn' en el nombre
    if ($file.Name -match 'crmpn') {
        $newFileName = $file.Name
        
        # Reemplazar en el nombre del archivo
        $newFileName = $newFileName -replace 'crmpn-', 'customers-manager-pn-'
        $newFileName = $newFileName -replace 'crmpn_', 'customers-manager-pn_'
        $newFileName = $newFileName -replace '\bcrmpn\b', 'customers-manager-pn'
        $newFileName = $newFileName -replace '\bCRMPN\b', 'CUSTOMERS_MANAGER_PN'
        
        if ($newFileName -ne $file.Name) {
            $newFilePath = Join-Path $file.DirectoryName $newFileName
            
            if (-not $WhatIf) {
                try {
                    Rename-Item -Path $filePath -NewName $newFileName -ErrorAction Stop
                    Write-Host "[RENOMBRADO] $($file.Name) -> $newFileName" -ForegroundColor Magenta
                    $fileRenamed = $true
                    $filesRenamed++
                } catch {
                    Write-Host "[ERROR] No se pudo renombrar: $($file.Name) - $_" -ForegroundColor Red
                }
            } else {
                Write-Host "[SIMULACIÓN] Se renombraría: $($file.Name) -> $newFileName" -ForegroundColor Magenta
                $fileRenamed = $true
                $filesRenamed++
            }
        }
    }
    
    if ($fileProcessed -or $fileRenamed) {
        $filesProcessed++
    }
}

# 3. Renombrar carpetas que contengan 'crmpn'
Write-Host ""
Write-Host "Buscando carpetas para renombrar..." -ForegroundColor Cyan

$allDirectories = Get-ChildItem -Path $Path -Recurse -Directory | Sort-Object -Property FullName -Descending

foreach ($dir in $allDirectories) {
    if ($dir.Name -match 'crmpn') {
        $newDirName = $dir.Name
        
        # Reemplazar en el nombre de la carpeta
        $newDirName = $newDirName -replace 'crmpn-', 'customers-manager-pn-'
        $newDirName = $newDirName -replace 'crmpn_', 'customers-manager-pn_'
        $newDirName = $newDirName -replace '\bcrmpn\b', 'customers-manager-pn'
        $newDirName = $newDirName -replace '\bCRMPN\b', 'CUSTOMERS_MANAGER_PN'
        
        if ($newDirName -ne $dir.Name) {
            $newDirPath = Join-Path $dir.Parent.FullName $newDirName
            
            if (-not $WhatIf) {
                try {
                    Rename-Item -Path $dir.FullName -NewName $newDirName -ErrorAction Stop
                    Write-Host "[CARPETA] $($dir.Name) -> $newDirName" -ForegroundColor Cyan
                } catch {
                    Write-Host "[ERROR] No se pudo renombrar carpeta: $($dir.Name) - $_" -ForegroundColor Red
                }
            } else {
                Write-Host "[SIMULACIÓN] Se renombraría carpeta: $($dir.Name) -> $newDirName" -ForegroundColor Cyan
            }
        }
    }
}

# Resumen
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "RESUMEN" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Archivos procesados: $filesProcessed" -ForegroundColor Green
Write-Host "Archivos renombrados: $filesRenamed" -ForegroundColor Green
Write-Host "Total de reemplazos: $replacementsCount" -ForegroundColor Green

if ($WhatIf) {
    Write-Host ""
    Write-Host "Modo SIMULACIÓN: No se realizaron cambios reales" -ForegroundColor Yellow
    Write-Host "Ejecuta sin -WhatIf para aplicar los cambios" -ForegroundColor Yellow
} else {
    Write-Host ""
    Write-Host "¡Proceso completado!" -ForegroundColor Green
    if ($Backup -and $backupLocation) {
        Write-Host "Backup disponible en: $backupLocation" -ForegroundColor Yellow
    }
}

