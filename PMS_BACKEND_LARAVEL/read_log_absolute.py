import sys
import os

log_path = 'c:\\Users\\USER\\Workspaces\\htdocs\\HR Onboarding\\hr\\hr\\storage\\logs\\laravel.log'

try:
    if os.path.exists(log_path):
        # Use 'utf-16' to auto-detect BOM
        with open(log_path, 'r', encoding='utf-16', errors='ignore') as f:
            content = f.read()
            if len(content) == 0:
                print("Content is empty in Python read")
            else:
                print(f"Read {len(content)} characters. Last 3000:")
                print(content[-3000:])
    else:
        print(f"File not found: {log_path}")
except Exception as e:
    print(f"Error: {e}")
print("Script finished")
