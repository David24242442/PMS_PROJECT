const fs = require('fs');
const content = fs.readFileSync('c:\\Users\\USER\\Workspaces\\htdocs\\HR Onboarding\\hrproject Vue\\hrproject\\src\\views\\app\\pms\\ReviewView.vue', 'utf8');

const countMatches = (regex) => (content.match(regex) || []).length;

const opens = countMatches(/<div/g);
const closes = countMatches(/<\/div>/g);

const textAreasOpen = countMatches(/<textarea/g);
const textAreasClose = countMatches(/<\/textarea>/g);

const spansOpen = countMatches(/<span/g);
const spansClose = countMatches(/<\/span>/g);

console.log(`DIV opens: ${opens}, Closes: ${closes}`);
console.log(`TEXTAREA opens: ${textAreasOpen}, Closes: ${textAreasClose}`);
console.log(`SPAN opens: ${spansOpen}, Closes: ${spansClose}`);
if (opens !== closes) console.log(`Mismatch in DIV!`);
if (textAreasOpen !== textAreasClose) console.log(`Mismatch in TEXTAREA!`);
if (spansOpen !== spansClose) console.log(`Mismatch in SPAN!`);
