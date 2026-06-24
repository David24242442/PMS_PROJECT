# edit_review_attachments_regex.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# Better Regex for single line item
$pattern = '(<div[^>]*class="flex items-center gap-2 overflow-hidden"[^>]*><i[^>]*class="pi pi-file[^>]*"><\/i><p[^>]*>\{\{\s*file\.file_name\s*\|\|\s*file\.name\s*\}\}<\/p><\/div>)'

$replacement = '<div class="flex items-center gap-2 overflow-hidden flex-1"><i class="pi pi-file text-xs text-[#1A237E]"></i><p class="truncate text-[10px] font-bold text-gray-700">{{ file.file_name || file.name }}</p></div><div class="flex items-center gap-1"><a v-if="file.url || file.file_path" :href="file.url || (''/storage/'' + file.file_path)" target="_blank" class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm cursor-pointer" title="View Attachment"><i class="pi pi-external-link text-[9px]"></i></a></div>'

if ($content -match $pattern) {
    $content = $content -replace $pattern, $replacement
    # Write preserving standard file setups 
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "File update completed with robust Regex flawlessly setups!"
} else {
    Write-Host "Robust target pattern NOT found in index setup."
}
