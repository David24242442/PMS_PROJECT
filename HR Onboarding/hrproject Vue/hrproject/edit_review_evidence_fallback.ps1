# edit_review_evidence_fallback.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# Replace the empty attachments state to include flat Evidence strings fallback
$target = '<div v-if="!parseList(quarter.attachments)?.length" class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider py-4 text-center border-2 border-dashed border-gray-100 rounded-lg">No files uploaded</div>'

$replacement = '<div v-if="quarter.evidence && (quarter.evidence.includes(''/'') || quarter.evidence.includes(''http''))" class="flex items-center justify-between p-2 bg-blue-50/50 rounded-lg border border-blue-100 mt-1 mb-1"><div class="flex items-center gap-2 overflow-hidden flex-1"><i class="pi pi-file-o text-xs text-[#1A237E]"></i><p class="truncate text-[10px] font-bold text-gray-700">Flat Evidence File</p></div><a :href="quarter.evidence.startsWith(''http'') ? quarter.evidence : (''/storage/'' + quarter.evidence)" target="_blank" class="w-6 h-6 rounded-md bg-white border flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm cursor-pointer" title="View Evidence"><i class="pi pi-external-link text-[9px]"></i></a></div><div v-if="!parseList(quarter.attachments)?.length && (!quarter.evidence || (!quarter.evidence.includes(''/'') && !quarter.evidence.includes(''http'')))" class="text-[9px] text-gray-400 font-semibold uppercase tracking-wider py-4 text-center border-2 border-dashed border-gray-100 rounded-lg">No files uploaded</div>'

if ($content.Contains($target)) {
    $content = $content.Replace($target, $replacement)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Evidence flat fallback appended flawlessly triggers setup setups!"
} else {
    Write-Host "Target empty attachments string NOT found for flat triggers."
}
