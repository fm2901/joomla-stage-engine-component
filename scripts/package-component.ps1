Param(
    [string]$Source = "joomla-component/com_stageengine",
    [string]$OutDir = "dist",
    [string]$ZipName = "com_stageengine.zip"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

if (!(Test-Path $Source)) {
    throw "Source not found: $Source"
}

if (!(Test-Path $OutDir)) {
    New-Item -ItemType Directory -Path $OutDir | Out-Null
}

$zipPath = Join-Path $OutDir $ZipName
if (Test-Path $zipPath) {
    Remove-Item $zipPath -Force
}

@"
from pathlib import Path
import zipfile

source = Path(r"$Source")
zip_path = Path(r"$zipPath")

if not source.exists():
    raise SystemExit(f"Source not found: {source}")

with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
    for p in source.rglob("*"):
        if p.is_dir():
            continue
        rel = p.relative_to(source).as_posix()
        zf.write(p, rel)

print(f"Component package created: {zip_path}")
"@ | python -
