
$quellVerzeichnis = ".\src\"
$ausgabeDatei     = ".\fkapi.txt"
$erlaubteEndungen = @("*.php", "*.json")

# Bestehende Ausgabedatei löschen
if (Test-Path $ausgabeDatei) { Remove-Item $ausgabeDatei }

# Dateien rekursiv und gefiltert einlesen
$dateien = Get-ChildItem -Path $quellVerzeichnis -File -Recurse -Include $erlaubteEndungen | Where-Object { $_.FullName -ne (Resolve-Path $ausgabeDatei -ErrorAction SilentlyContinue).Path }

foreach ($datei in $dateien) {
    # Strukturierte Trennlinie mit dem vollständigen Pfad
    $trennlinie = "`r`n" + ("=" * 80) + "`r`n" + "PFAD: $($datei.FullName)" + "`r`n" + ("=" * 80) + "`r`n"
    
    Add-Content -Path $ausgabeDatei -Value $trennlinie -Encoding utf8
    
    # Inhalt der Datei anhängen
    if ($datei.Length -gt 0) {
        $inhalt = Get-Content -Path $datei.FullName -Raw
        Add-Content -Path $ausgabeDatei -Value $inhalt -Encoding utf8
    } else {
        Add-Content -Path $ausgabeDatei -Value "[Datei ist leer]" -Encoding utf8
    }
}