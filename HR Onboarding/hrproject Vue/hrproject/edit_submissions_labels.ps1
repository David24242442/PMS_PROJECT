$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# 1. Update items-center to items-end to align bottom after adding top labels
$content = $content -replace 'items-stretch lg:items-center gap-4">', 'items-stretch lg:items-end gap-4">'

# 2. Add Label over Search Grid
$searchPattern = '<!-- Advanced Search -->\s*<div class="flex-1 relative group">'
$searchReplacement = '<!-- Advanced Search -->
                <div class="flex-1 space-y-1.5">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Search Records</label>
                    <div class="relative group">'

$content = $content -replace $searchPattern, $searchReplacement

# 3. Insert closing tag for that search wrapper
$searchClosePattern = 'placeholder="Search by employee name or code\.\.\." />\s*</div>'
$searchCloseReplacement = 'placeholder="Search by employee name or code..." />
                    </div>
                </div>'

$content = $content -replace $searchClosePattern, $searchCloseReplacement

# 4. Add Label over Department Grid Select
$deptPattern = '<!-- Department Protocol -->\s*<div class="relative group min-w-\[200px\]">'
$deptReplacement = '<!-- Department Protocol -->
                    <div class="space-y-1.5 min-w-[200px]">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Department</label>
                        <div class="relative group">'

$content = $content -replace $deptPattern, $deptReplacement

# 5. Insert closing tag for that department wrapper
$deptClosePattern = 'group-hover:scale-110 transition-transform"></i>\s*</div>'
$deptCloseReplacement = 'group-hover:scale-110 transition-transform"></i>
                        </div>
                    </div>'

$content = $content -replace $deptClosePattern, $deptCloseReplacement

Set-Content $path $content -NoNewline
Write-Host "Labels Applied successfully"
