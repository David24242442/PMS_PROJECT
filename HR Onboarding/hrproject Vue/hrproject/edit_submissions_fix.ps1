$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# Replace single closing div tag with double around the search input deck
$pattern = 'placeholder="Search by employee name or code\.\.\." />\s*</div>\s*<div class="flex flex-col md:flex-row'
$replacement = 'placeholder="Search by employee name or code..." />
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row'

$content = $content -replace $pattern, $replacement

# Verify if other imbalances exist by reading file or just saving
Set-Content $path $content -NoNewline
Write-Host "Tag fixed successfully"
