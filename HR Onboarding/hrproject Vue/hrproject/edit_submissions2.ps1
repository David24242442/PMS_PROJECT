$filePath = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content -Path $filePath -Raw

# 1. Remove Cycle Year in Date column
$oldDate = '<span class="text-\[9px\] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Cycle \{\{ currentYear \}\}<\/span>'
if ($content -match $oldDate) {
    $content = $content -replace $oldDate, ""
    Write-Host "Matched date."
} else {
    Write-Host "No match for date."
}

# 2. Fix status color tags flatly to emerald
$oldStatusSpan = '<span class="px-4 py-1.5 rounded-full text-\[9px\] font-black uppercase tracking-widest border inline-flex items-center gap-2 shadow-sm"\s*:class="item\.status === ''approved'' \? ''bg-emerald-50 text-emerald-700 border-emerald-100 shadow-emerald-100/20'' : ''bg-orange-50 text-orange-700 border-orange-100 shadow-orange-100/20''">'
$newStatusSpan = '<span class="px-4 py-1.5 rounded-full text-[9px] font-black uppercase tracking-widest border border-emerald-100 inline-flex items-center gap-2 shadow-sm bg-emerald-50 text-emerald-700 shadow-emerald-100/20">'

# Fix dot
$oldDot = '<span class="w-1.5 h-1.5 rounded-full animate-pulse"\s*:class="item\.status === ''approved'' \? ''bg-emerald-500'' : ''bg-orange-500''"><\/span>'
$newDot = '<span class="w-1.5 h-1.5 rounded-full animate-pulse bg-emerald-500"></span>'

if ($content -match $oldStatusSpan) {
    $content = $content -replace $oldStatusSpan, $newStatusSpan
    Write-Host "Matched status span."
} else {
    Write-Host "No match for status span."
}

if ($content -match $oldDot) {
    $content = $content -replace $oldDot, $newDot
    Write-Host "Matched dot."
} else {
    Write-Host "No match for dot."
}

Set-Content -Path $filePath -Value $content -NoNewline
Write-Host "Submissions updated."
