# edit_review_attachments_download_fix.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

$target = ':download="quarter.evidence.Split(''/'')[-1] || ''evidence''"'
$replacement = ':download="quarter.evidence.split(''/'').pop() || ''evidence''"'

if ($content.Contains($target)) {
    $content = $content.Replace($target, $replacement)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Download syntax corrected to JS flawlessly triggers setups!"
} else {
    Write-Host "Target typo string NOT found for correction setups frames."
}
