import fs from 'fs';

const content = fs.readFileSync('c:\\Users\\USER\\Workspaces\\htdocs\\HR Onboarding\\hrproject Vue\\hrproject\\src\\views\\app\\pms\\ReviewView.vue', 'utf8');

const lines = content.split('\n');
let depth = 0;

console.log("--- Starting DIV Tag Depth Tracking ---");
lines.forEach((line, index) => {
    const originalDepth = depth;
    // Count matches on this line
    const opens = (line.match(/<div/g) || []).length;
    const closes = (line.match(/<\/div>/g) || []).length;
    
    depth += opens - closes;
    
    // If closes > opens and depth < 0 on this line, it's an immediate error!
    if (depth < 0) {
        console.error(`[ERROR] Line ${index + 1}: Over-closed! Depth became ${depth}`);
    }
    
    if (opens > 0 || closes > 0) {
        // Optional: comment out verbose logs unless depth changes or has error
        console.log(`Line ${index + 1}: Opens: +${opens}, Closes: -${closes} | Depth: ${originalDepth} -> ${depth} | Content: ${line.trim().substring(0, 40)}`);
    }
});

console.log(`--- Finished --- Final Depth: ${depth}`);
if (depth !== 0) {
    console.error(`[FAIL] Mismatch! Final depth is ${depth}`);
} else {
    console.log(`[OK] Divs are balanced!`);
}
