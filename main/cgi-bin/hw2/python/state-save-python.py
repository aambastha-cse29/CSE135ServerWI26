#!/usr/bin/python3
import os, urllib.parse, secrets, json

SESS_DIR = "/tmp/python_sessions"
COOKIE_NAME = "sid"

os.makedirs(SESS_DIR, exist_ok=True)

# Read Cookies
cookies = {}
http_cookie = os.environ.get("HTTP_COOKIE", "")
if http_cookie:
    for cookie in http_cookie.split(";"):
        cookie = cookie.strip()
        if "=" in cookie:
            name, value = cookie.split("=", 1)
            cookies[name.strip()] = value.strip()


# Get session ID from cookie
session_id = cookies.get(COOKIE_NAME, "")

# Read POST data
n = int(os.environ.get("CONTENT_LENGTH", "0") or "0")
body = os.read(0, n).decode("utf-8", errors="replace") if n > 0 else ""
qs = urllib.parse.parse_qs(body, keep_blank_values=True)
data = {k: (v[0] if v else "") for k, v in qs.items()}

if not data:
    if session_id:
         print("Status: 302 Found")
         print("Location: /cgi-bin/hw2/python/state-view-python.py")
         print()
    else:
         print("Status: 302 Found")
         print("Location: /state-collect-python.html")
         print()

    exit()


if not session_id:
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
print("<head>")  
print("  <meta charset='UTF-8'>")   
print("  <meta name='viewport' content='width=device-width, initial-scale=1.0'>")   
print("  <title>Data Saved -- Python</title>")   
print("  <style>")   
print("    body { font-family: Arial, sans-serif; max-width: 500px; margin: 50px auto; padding: 20px; text-align: center; }")
print("    h2 { color: #28a745; }")
print("    a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }")
print("    a:hover { background: #0056b3; }")
print("  </style>")
print("</head>")
print("<body>")
print("  <h2>✓ Data Saved Successfully! -- Python</h2>") 
print("  <p>Your Information Has Been Stored In The Session</p>") 
print("  <a href='/cgi-bin/hw2/python/state-view-python.py'>View Saved Data</a>") 
print("</body>")
print("</html>")