$path = "c:\Users\USER\Workspaces\htdocs\HR Onboarding\hrproject Vue\hrproject\src\views\app\pms\DashboardView.vue"
$content = Get-Content $path -Raw

# 1. Inject helper parsers and calculation functions above stats computing
$helpers = @"
const parseSmart = (data) => {
    if (!data) return {};
    if (typeof data === 'object' && !Array.isArray(data)) return data;
    try {
        return typeof data === 'string' ? JSON.parse(data) : data;
    } catch (e) {
        return {};
    }
};

const calculateAverageRating = (appraisal) => {
    if (!appraisal) return '0.0';
    const data = parseSmart(appraisal.appraisal_data || appraisal.goal_data);
    const competencies = data?.competencies || [];
    if (competencies.length === 0) return appraisal.overall_rating || '0.0';
    const sum = competencies.reduce((acc, c) => acc + (parseFloat(c.managerRating) || 0), 0);
    return (sum / competencies.length).toFixed(1);
};

const getTimeProgress = (goal) => {
    if (!goal || !goal.completion_date) return 0;
    const start = new Date(goal.submitted_at || goal.created_at || `2024-01-01`);
    const end = new Date(goal.completion_date);
    const today = new Date();
    if (today >= end) return 100;
    if (today <= start) return 0;
    return Math.min(100, Math.max(0, Math.round(((today - start) / (end - start)) * 100)));
};
"@

$content = $content -replace '// Top Employees for Leaderboard', "$helpers`n// Top Employees for Leaderboard"

# 2. Update Employee Subtext inside Recent Goals table Row
$content = $content -replace '\{\{ goal\.user\?\.name \|\| ''Unassigned'' \}\}', '{{ goal.employee?.name || goal.user?.name || ''Unassigned'' }}'

# 3. Update Rating Mock to calculateAverageRating(goal)
$content = $content -replace '\{\{ \(Math\.random\(\) \* \(5\.0 - 4\.0\) \+ 4\.0\)\.toFixed\(1\) \}\}', '{{ calculateAverageRating(goal) }}'

# 4. Update Progress math to Time Progress function and inject progress bar
$progressPattern = '\{\{ Math\.round\(\(goal\.actual / goal\.target\) \* 100\) \}\}% <span class="text-gray-400 font-normal">completed</span>'
$progressReplacement = @"
<div class="flex items-center gap-2">
                                        <div class="w-24 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="bg-indigo-600 h-1.5 rounded-full" :style="{ width: getTimeProgress(goal) + '%' }"></div>
                                        </div>
                                        <span>{{ getTimeProgress(goal) }}%</span>
                                    </div>
"@
$content = $content -replace $progressPattern, $progressReplacement

# 5. Leaderboard Update
$content = $content -replace 'emp\.score', 'emp.rating || emp.score'
$content = $content -replace 'Score</span>', 'Rating</span>'

Set-Content $path $content -NoNewline
Write-Host "Dashboard helpers injected and rows updated successfully"
