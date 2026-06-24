import fs from 'fs';

const content = fs.readFileSync('c:\\\\Users\\\\USER\\\\Workspaces\\\\htdocs\\\\HR Onboarding\\\\hrproject Vue\\\\hrproject\\\\src\\\\views\\\\app\\\\pms\\\\GoalsView.vue', 'utf8');
const lines = content.split('\n');

for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    if (line.includes('selectedCandidate') || line.includes('searchCandidate') || line.includes('candidate_name') || line.includes('department') || line.includes('location')) {
        if (line.includes('watch') || line.includes('function') || line.includes('const') || line.includes('let') || line.includes('=>') || line.includes('@')) {
             console.log(`${i+1}: ${line.trim()}`);
        }
    }
}
