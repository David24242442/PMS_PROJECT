# edit_review_attachments_blob_download.ps1
$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\ReviewView.vue"
$content = Get-Content $path -Raw

# 1. Inject downloadFile method before </script>
$scriptEnd = '</script>'
$method = @"
const downloadFile = async (url, filename) => {
    if (!url) return;
    try {
        const baseUrl = (axios.defaults.baseURL || '').replace(/\/api\/?$/, '');
        const fullUrl = url.startsWith('http') ? url : `\${baseUrl}/\${url.replace(/^\//, '')}`;
        
        const response = await axios({
            url: fullUrl,
            method: 'GET',
            responseType: 'blob'
        });
        
        const blob = new Blob([response.data], { type: response.headers['content-type'] || 'application/octet-stream' });
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = filename || 'download';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(link.href);
    } catch (error) {
        console.error('Download failed:', error);
        if (typeof showAlert === 'function') {
            showAlert('Error', 'Failed to download file.', 'error');
        } else {
            alert('Failed to download file.');
        }
    }
};

</script>
"@

if ($content.Contains($scriptEnd)) {
    # Replace ONLY the last occurrence of </script> securely setups
    $index = $content.LastIndexOf($scriptEnd)
    $content = $content.Substring(0, $index) + $method + $content.Substring($index + $scriptEnd.Length)

    # 2. Update Template 1 (Line 564 Attachments)
    # Target anchor injected in step 1580
    $pattern1 = '<a :href="file\.path \|\| file\.url \|\| \(''/storage/'' \+ \([^\)]+\)\)" :download="file\.name \|\| ''file''" target="_blank" class="w-7 h-7[^"]*" title="View/Download Attachment"><i class="pi pi-external-link text-xs"><\/i><\/a>'
    # Since previous write literal was standard string, let's use direct text replace inside powershell with -replace Regex!
    
    # Let's find exactly the anchor template string on line 564 to use a simple non-regex String.Replace
    $targetAnchor1 = '<a :href="file.path || file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file))" :download="file.name || ''file''" target="_blank" class="w-7 h-7 rounded-md bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20 cursor-pointer" title="View/Download Attachment"><i class="pi pi-external-link text-xs"></i></a>'
    $replacementAnchor1 = '<button @click="downloadFile(file.path || file.url || (''/storage/'' + (file.file_path || file.file_name || file.name || file)), file.name || ''file'')" class="w-7 h-7 rounded-md bg-blue-600 flex items-center justify-center text-white hover:bg-blue-700 transition-all shadow-md shadow-blue-500/20 cursor-pointer" title="Download Attachment"><i class="pi pi-download text-xs"></i></button>'

    # 3. Update Template 2 (Line 565 flat Evidence fallback)
    $targetAnchor2 = '<a :href="quarter.evidence.startsWith(''http'') ? quarter.evidence : (''/storage/'' + quarter.evidence)" :download="quarter.evidence.split(''/'').pop() || ''evidence''" target="_blank" class="w-6 h-6 rounded-md bg-white border flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm cursor-pointer" title="View Evidence"><i class="pi pi-external-link text-[9px]"></i></a>'
    $replacementAnchor2 = '<button @click="downloadFile(quarter.evidence.startsWith(''http'') ? quarter.evidence : (''/storage/'' + quarter.evidence), quarter.evidence.split(''/'').pop() || ''evidence'')" class="w-6 h-6 rounded-md bg-white border flex items-center justify-center text-blue-600 hover:bg-blue-600 hover:text-white transition-all shadow-sm cursor-pointer" title="Download Evidence"><i class="pi pi-download text-[9px]"></i></button>'

    $content = $content.Replace($targetAnchor1, $replacementAnchor1)
    $content = $content.Replace($targetAnchor2, $replacementAnchor2)

    [System.IO.File]::WriteAllText($path, $content)
    Write-Host "Blob Download injected flawlessly setups flawless!"
} else {
    Write-Host "Script end tag NOT found for method injection setup frames."
}
