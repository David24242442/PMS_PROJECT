const fs = require('fs');
const file = 'src/views/app/pms/ReviewView.vue';
let content = fs.readFileSync(file, 'utf8');
content = content.replace(/selectedGoal\.status === 'completed'/g, "selectedGoal.display_status === 'review_completed'");
content = content.replace(/selectedGoal\.status !== 'completed'/g, "selectedGoal.display_status !== 'review_completed'");
fs.writeFileSync(file, content, 'utf8');
console.log('done');
