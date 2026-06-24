import fs from 'fs';

const content = fs.readFileSync('c:\\Users\\USER\\Workspaces\\htdocs\\HR Onboarding\\hrproject Vue\\hrproject\\src\\views\\app\\pms\\GoalsView.vue', 'utf8');
const lines = content.split('\n');

console.log("--- Starting GoalsView.vue Tag Trace ---");
lines.forEach((line, index) => {
    // Search for axios or status or submitted or approved
    const match = line.toLowerCase().includes('axios') || line.toLowerCase().includes('status') || line.toLowerCase().includes('submitted');
    if (match) {
        console.log(`Line ${index + 1}: ${line.trim().substring(0, 100)}`);
    }
});
console.log("--- Finished ---");
