# edit_review_attachments_blob_baseurl.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# 1. Target the broken fullUrl definition block
$target = 'const baseUrl = (axios.defaults.baseURL || '''').replace(/\/api\/?$/, '''');
        const cleanUrl = url.replace(/^\/?storage\//, "public/storage/");
        const fullUrl = url.startsWith("http") ? url : `${baseUrl}/${cleanUrl.replace(/^\//, "")}`;
        
        const response = await axios({
            url: fullUrl,
            method: ''GET'',
            responseType: ''blob''
        });'

$replacement = 'const response = await axios({
            url: url,
            baseURL: '''',
            method: ''GET'',
            responseType: ''blob''
        });'

if ($content.Contains('const cleanUrl = url.replace(/^\/?storage\//, "public/storage/");')) {
    $content = $content.Replace($target, $replacement)
    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Axios baseURL set to empty flawlessly triggers setups!"
} else {
    Write-Host "Target fullUrl cleanUrl block NOT found for substitution setups frames."
}
