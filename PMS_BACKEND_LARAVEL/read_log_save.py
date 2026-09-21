log_path = 'storage/logs/laravel.log'
out_path = 'read_log_output.txt'

with open(out_path, 'w') as out:
    out.write("Script started\n")
    try:
        import os
        if os.path.exists(log_path):
            size = os.path.getsize(log_path)
            out.write(f"Log exists, size: {size} bytes\n")
            with open(log_path, 'r', encoding='utf-16le', errors='ignore') as f:
                content = f.read()
                out.write(f"Read {len(content)} characters from log\n")
                lines = content.split('\n')
                out.write(f"Total lines: {len(lines)}\n")
                tail = lines[-150:]
                out.write("\n--- TAIL START ---\n")
                out.write('\n'.join(tail))
                out.write("\n--- TAIL END ---\n")
        else:
            out.write(f"File not found: {log_path}\n")
    except Exception as e:
        out.write(f"Error reading log: {e}\n")

    out.write("Script finished\n")
print("Done writing to read_log_output.txt")
