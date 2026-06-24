# edit_review_attachments_visible.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

$pattern = '<a v-if="file\[\.\]url \|\| file\[\.\]file_path" :href="file\[\.\]url \|\| \(''/storage/'' \+ file\[\.\]file_path\)"'
# Since previous write was literal: <a v-if="file.url || file.file_path" :href="file.url || ('/storage/' + file.file_path)"
# Let's do a direct text replace which is 100% accurate!

$target = '<a v-if="file.url || file.file_path" :href="file.url || (''/storage/'' + file.file_path)"'
$replacement = '<a :href="file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))"'

if ($content.Contains('<a v-if="file.url || file.file_path"')) {
    $content = $content.Replace($target, $replacement)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Attachment v-if removed and fallback URL setup flawless fixes setups!"
} else {
    Write-Host "Target anchor string NOT found for v-if triggers setup frames."
}
