import sys

log_path = 'storage/logs/laravel.log'

try:
    with open(log_path, 'r', encoding='utf-16le', errors='ignore') as f:
        content = f.read()
        lines = content.split('\n')
        tail = lines[-150:]
        print("\n--- TAIL START ---")
        print('\n'.join(tail))
        print("--- TAIL END ---")
except Exception as e:
    print(f"Error reading log: {e}")
