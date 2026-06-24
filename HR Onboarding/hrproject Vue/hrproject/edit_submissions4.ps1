$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\hr\SubmissionsView.vue"
$content = Get-Content $path -Raw

# Replace 4 divs before Signature with 3 divs before Signature
$newContent = $content -replace '(\s*</div>\s*</div>\s*</div>\s*</div>\s*)\s*(<div class="mt-20 pt-10 border-t-2 border-slate-900)', '`r`n`t`t`t`t`t`t`t`t`t </div>`r`n`t`t`t`t`t`t`t`t </div>`r`n`t`t`t`t`t`t`t </div>`r`n`r`n`t`t`t`t`t`t`t`t $2'

Set-Content $path $newContent -NoNewline
Write-Host "Replaced successfully"
