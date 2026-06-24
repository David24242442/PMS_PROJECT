import fs from 'fs';
import path from 'path';

function walkDir(dir, callback) {
    const files = fs.readdirSync(dir);
    for (const file of files) {
        const fullPath = path.join(dir, file);
        if (fs.statSync(fullPath).isDirectory()) {
            walkDir(fullPath, callback);
        } else {
            callback(fullPath);
        }
    }
}

const dirPath = 'c:\\\\Users\\\\USER\\\\Workspaces\\\\htdocs\\\\HR Onboarding\\\\hrproject Vue\\\\hrproject\\\\src\\\\views';

walkDir(dirPath, (filePath) => {
    if (filePath.endsWith('.vue') || filePath.endsWith('.js')) {
        const content = fs.readFileSync(filePath, 'utf8');
        if (content.toLowerCase().includes('joining') || content.toLowerCase().includes('department') || content.toLowerCase().includes('location')) {
            const lines = content.split('\n');
            for (let i = 0; i < lines.length; i++) {
                const line = lines[i];
                if (line.includes('Joining') || line.includes('Department') || line.includes('Location')) {
                    if (filePath.includes('Employee') || filePath.includes('Goals') || filePath.includes('Review')) {
                         console.log(`${filePath}: ${i+1}: ${line.trim()}`);
                    }
                }
            }
        }
    }
});
