# edit_review_debug_evidence.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

$target = '<div v-if="quarter.evidence && (quarter.evidence.includes'
$replacement = '<div class="text-[8px] text-red-500 font-bold mt-1">Debug: A:{{ quarter.attachments }} | E:{{ quarter.evidence }}</div><div v-if="quarter.evidence && (quarter.evidence.includes'

if ($content.Contains($target)) {
    $content = $content.Replace($target, $replacement)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Evidence debug triggers appended flawlessly fixes setups!"
} else {
    Write-Host "Target anchor string NOT found for debug setup frames."
}
