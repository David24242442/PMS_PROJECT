# edit_review_attachments.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# Regex matching the Evidence loop item contents flawlessly
$pattern = '(?s)(<div v-for="\(file, fIndex\) in parseList\(quarter\.attachments\)" :key="fIndex" class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border">\s*<div class="flex items-center gap-2 overflow-hidden">.*?\{\{ file\.file_name \|\| file\.name \}\}<\/p><\/div>)(\s*<\/div>)'

$replacement = '$1<div class="flex items-center gap-1"><a v-if="file.url || file.file_path" :href="file.url || (''/storage/'' + file.file_path)" target="_blank" class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm cursor-pointer" title="View Attachment"><i class="pi pi-external-link text-[9px]"></i></a></div>$2'

if ($content -match $pattern) {
    $content = $content -replace $pattern, $replacement
    $content | Out-File -FilePath $path -Encoding utf8NoBOM
    Write-Host "File update completed with upload button flawlessly setups!"
} else {
    Write-Host "Pattern match failed on Evidence loop setup flawlessly layouts."
}
