# edit_review_attachments_path.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# 1. Update the anchor to prioritizing file.path and make it brighter and remove debug next step
$targetAnchor = '<a :href="file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))" target="_blank" class="w-6 h-6 rounded-md bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-all shadow-sm cursor-pointer" title="View Attachment"><i class="pi pi-external-link text-[9px]"></i></a>'

# Large blue button to leave no doubt of visibility
$replacementAnchor = '<a :href="file.path || file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))" target="_blank" class="w-7 h-7 rounded-md bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20 cursor-pointer" title="View/Download Attachment"><i class="pi pi-external-link text-xs"></i></a>'

# 2. Remove the debug line added in previous step
$targetDebug = '<div class="text-[8px] text-red-500 font-bold mt-1">Debug: A:{{ quarter.attachments }} | E:{{ quarter.evidence }}</div>'

if ($content.Contains($targetAnchor)) {
    $content = $content.Replace($targetAnchor, $replacementAnchor)
    $content = $content.Replace($targetDebug, "")
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Attachment anchor path and styling adjusted flawlessly triggers setups!"
} else {
    Write-Host "Target anchor string NOT found for path fixing setups frames."
}
