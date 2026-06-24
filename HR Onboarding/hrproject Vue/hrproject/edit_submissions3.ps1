$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# Replace 3 divs before Dialog with 4 divs before Dialog to restore outer wrapper
$newContent = $content -replace '(</div>\s*</div>\s*</div>\s*)(</Dialog>)', '$1</div>`r`n$2'

Set-Content $path $newContent -NoNewline
Write-Host "Replaced successfully"
