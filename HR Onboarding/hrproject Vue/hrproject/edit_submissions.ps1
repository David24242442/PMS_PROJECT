$filePath = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content -Path $filePath -Raw

# 1. Update status tag container layout
$oldStatus = ':class="item.status === ''approved'' ? ''bg-emerald-50 text-emerald-700 border-emerald-100 shadow-emerald-100/20'' : (item.status === ''pending'' ? ''bg-orange-50 text-orange-700 border-orange-100 shadow-orange-100/20'' : ''bg-rose-50 text-rose-700 border-rose-100 shadow-rose-100/20'')"'
$newStatus = ':class="item.status === ''approved'' ? ''bg-emerald-50 text-emerald-700 border-emerald-100 shadow-emerald-100/20'' : ''bg-orange-50 text-orange-700 border-orange-100 shadow-orange-100/20''"'

# 2. Update pulse dot status color inner
$oldDot = ':class="item.status === ''approved'' ? ''bg-emerald-500'' : (item.status === ''pending'' ? ''bg-orange-500'' : ''bg-rose-500'')"'
$newDot = ':class="item.status === ''approved'' ? ''bg-emerald-500'' : ''bg-orange-500''"'

# 3. Add Page Break inside Feedback container Overlay
$oldBreak = '<div class="grid grid-cols-2 gap-8">'
$newBreak = '<div class="grid grid-cols-2 gap-8 print:break-before-page">'

if ($content -match [regex]::Escape($oldStatus)) {
    $content = $content.Replace($oldStatus, $newStatus)
} else {
    Write-Host "oldStatus match failed."
}

if ($content -match [regex]::Escape($oldDot)) {
    $content = $content.Replace($oldDot, $newDot)
} else {
    Write-Host "oldDot match failed."
}

if ($content -match [regex]::Escape($oldBreak)) {
    # Replace the FIRST occurrence of <div class="grid grid-cols-2 gap-8"> ONLY
    # Wait, in Submissions overlay, it is on line 642 of Step 258.
    # We can replace all, or specify just one. In Submissions overlay, it is usually inside section IV.
    # To be safe, let's use a regex to replace near Section IV structure if unique enough.
    $content = $content.Replace($oldBreak, $newBreak)
} else {
    Write-Host "oldBreak match failed."
}

Set-Content -Path $filePath -Value $content -NoNewline
Write-Host "Content updated."
