#!/usr/bin/python3
import os, urllib.parse, secrets, json

SESS_DIR = "/tmp/python_sessions"
COOKIE_NAME = "sid"

os.makedirs(SESS_DIR, exist_ok=True)

# Read POST data
n = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
body = os.read(0, n).decode("utf-8", errors="replace") if n > 0 else ""
qs = urllib.parse.parse_qs(body, keep_blank_values=True)
data = {k: (v[0] if v else "") for k, v in qs.items()}

# Generate session ID
session_id = secrets.token_hex(16)

# Save session data to file
path = os.path.join(SESS_DIR, f"{session_id}.json")
with open(path, "w", encoding="utf-8") as f:
    json.dump(data, f)

# Send CGI headers
print("Set-Cookie: " + f"{COOKIE_NAME}={session_id}; Path=/; Max-Age=86400; HttpOnly; SameSite=Lax; Secure")
print("Content-Type: text/html")
print()

# HTML output
print("<!DOCTYPE html>")
print("<html>")
print("<head><title>Data Saved</title></head>")
print("<body>")
print("<h2>Data Saved Successfully!</h2>")
print("<p><a href='/cgi-bin/hw2/python/state-view-python.py'>View Saved Data</a></p>")
print("</body>")
print("</html>")