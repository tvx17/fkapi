#requires -Version 5.1
<#
.SYNOPSIS
Nimmt die gespeicherte Codex-Sitzung dieses Projekts wieder auf.
.EXAMPLE
.\Resume-Sitzung.ps1
.EXAMPLE
.\Resume-Sitzung.ps1 -Auswahl
.EXAMPLE
.\Resume-Sitzung.ps1 -WhatIf
#>
[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [ValidateNotNullOrEmpty()]
    [string] $SessionId = '01a11495-5604-7530-9352-e827dfebbc29',

    [switch] $Auswahl
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectPath = $PSScriptRoot
if (-not (Test-Path -LiteralPath (Join-Path $projectPath 'composer.json') -PathType Leaf)) {
    throw 'Das Skript muss im Stammverzeichnis des LaravelAPI-Projekts liegen.'
}

$codexCommand = Get-Command codex -CommandType Application, ExternalScript -ErrorAction SilentlyContinue |
    Select-Object -First 1
if (-not $codexCommand) {
    throw 'Codex CLI wurde nicht im PATH gefunden. Installiere Codex und melde dich an, bevor du die Sitzung fortsetzt.'
}

$codexArguments = @('resume', '--cd', $projectPath)
if (-not $Auswahl) {
    $codexArguments += $SessionId
}

$resumeTarget = if ($Auswahl) { 'Sitzungsauswahl fuer dieses Projekt' } else { "Sitzung $SessionId" }
if ($PSCmdlet.ShouldProcess($projectPath, "Codex starten: $resumeTarget")) {
    Push-Location -LiteralPath $projectPath
    try {
        & $codexCommand.Source @codexArguments
        if ($LASTEXITCODE -ne 0) {
            throw "Codex wurde mit Exitcode $LASTEXITCODE beendet. Falls die gespeicherte Sitzung fehlt, starte das Skript mit -Auswahl."
        }
    } finally {
        Pop-Location
    }
}
