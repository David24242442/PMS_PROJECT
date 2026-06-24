$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# 1. Remove KPI Cards Grid (Lines 318 - 395 approx)
$pattern1 = '(?s)<!-- Refined KPI Row -->.*?<!-- System Governance Filter Deck -->'
$content = $content -replace $pattern1, '<!-- System Governance Filter Deck -->'

# 2. Update Search Bar Placeholder & Padding
$content = $content -replace 'placeholder="QUERY SYSTEM BY EMPLOYEE NAME OR CODE\.\.\."', 'placeholder="Search by employee name or code..."'
$content = $content -replace 'py-5 bg-slate-50', 'py-3.5 bg-slate-50'

# 3. Update Department Select Options
$content = $content -replace '<option value="">All Functional Units</option>', '<option value="">All Departments</option>'

# 4. Update Table Header Row BG from indigo-900 to slate-50 and text-white to text-slate-700
$content = $content -replace '<tr class="bg-indigo-900 text-white', '<tr class="bg-slate-50 text-slate-700 border-b border-slate-200'

Set-Content $path $content -NoNewline
Write-Host "Replaced successfully"
