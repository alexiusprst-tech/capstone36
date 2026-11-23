param([Parameter(Mandatory)][string]$InputDocx, [Parameter(Mandatory)][string]$OutputPdf)
$ErrorActionPreference = 'Stop'
$word = New-Object -ComObject Word.Application
$discardChanges = 0
try {
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $word.AutomationSecurity = 3
    $document = $word.Documents.Open($InputDocx, $false, $true, $false)
    try {
        $document.ExportAsFixedFormat($OutputPdf, 17)
    } finally {
        $document.Close([ref]$discardChanges)
    }
} finally {
    $word.Quit([ref]$discardChanges)
}
