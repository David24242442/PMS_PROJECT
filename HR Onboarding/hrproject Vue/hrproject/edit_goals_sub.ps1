$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\GoalsView.vue"
$content = Get-Content $path -Raw

# Replace selectedGoal status condition with regex to survive trailing spaces
$newContent = $content -replace '(<button\s+v-if="selectedGoal\.status\s*!==\s*''completed''\s*&&\s*selectedGoal\.status\s*!==\s*''approved'')', '<button v-if="selectedGoal.status === ''draft''"'

Set-Content $path $newContent -NoNewline
Write-Host "Replaced successfully"
