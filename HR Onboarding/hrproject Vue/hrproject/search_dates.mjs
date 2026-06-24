import fs from 'fs';

const content = fs.readFileSync('c:\\\\Users\\\\USER\\\\Workspaces\\\\htdocs\\\\HR Onboarding\\\\hrproject Vue\\\\hrproject\\\\src\\\\views\\\\app\\\\pms\\\\GoalsView.vue', 'utf8');
const lines = content.split('\n');

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    if (line.includes('start_date') || line.includes('quarterly_tracking') || line.includes('defaultQuarterlyTracking')) {
        console.log(`${i+1}: ${line.trim()}`);
    }
}
