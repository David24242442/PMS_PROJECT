import os

log_path = 'storage/logs/laravel.log'

if not os.path.exists(log_path):
    print(f"File not found: {log_path}")
    # Try looking in parent/current dir
    print(f"Current Dir: {os.getcwd()}")
    print("Files in storage/logs:")
    if os.path.exists('storage/logs'):
        print(os.listdir('storage/logs'))
else:
    size = os.path.getsize(log_path)
    print(f"Log size: {size} bytes")
    with open(log_path, 'r', encoding='utf-8', errors='ignore') as f:
        lines = f.readlines()
        print(f"Total lines: {len(lines)}")
        tail = lines[-100:]
        print("\n--- TAIL START ---")
        print(''.join(tail))
        print("--- TAIL END ---")
