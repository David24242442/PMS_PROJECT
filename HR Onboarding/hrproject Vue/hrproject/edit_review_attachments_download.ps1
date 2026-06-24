# edit_review_attachments_download.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# 1. Update first anchor (Step 2 Attachments)
$target1 = '<a :href="file.path || file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))" target="_blank"'
$replacement1 = '<a :href="file.path || file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))" :download="file.name || ''file''" target="_blank"'

# 2. Update second anchor (Step 2 Evidence Fallback line 566)
$target2 = '<a :href="quarter.evidence.startsWith(''http'') ? quarter.evidence : (''/storage/'' + quarter.evidence)" target="_blank"'
$replacement2 = '<a :href="quarter.evidence.startsWith(''http'') ? quarter.evidence : (''/storage/'' + quarter.evidence)" :download="quarter.evidence.Split(''/'')[-1] || ''evidence''" target="_blank"'

if ($content.Contains($target1)) {
    $content = $content.Replace($target1, $replacement1)
    $content = $content.Replace($target2, $replacement2)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Download attribute appended flawlessly triggers setups!"
} else {
    Write-Host "Target anchor string NOT found for download setup frames."
}
