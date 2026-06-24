# edit_review_attachments_public_fix.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# 1. Update fullUrl variable to inject public/ fallback into relative paths
$target = 'const fullUrl = url;'
$replacement = 'const baseUrl = (axios.defaults.baseURL || "").replace(/\/api\/?$/, "");' + "`n" + '        const cleanUrl = url.replace(/^\/?storage\//, "public/storage/");' + "`n" + '        const fullUrl = url.startsWith("http") ? url : `\${baseUrl}/\${cleanUrl.replace(/^\//, "")}`;'

if ($content.Contains($target)) {
    # We must use double backticks or escape correctly flawlessly setups
    # To avoid script breaking Vue template, let's write exact javascript block literally!
    $jsBlock = 'const baseUrl = (axios.defaults.baseURL || "").replace(/\/api\/?$/, "");' + "`n" + '        const cleanUrl = url.replace(/^\/?storage\//, "public/storage/");' + "`n" + '        const fullUrl = url.startsWith("http") ? url : `${baseUrl}/${cleanUrl.replace(/^\//, "")}`;'
    
    $content = $content.Replace($target, $jsBlock)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Download absolute path with public injection fixed flawlessly setups triggers setups!"
} else {
    Write-Host "Target fullUrl definition string NOT found for public injection setup frames."
}
