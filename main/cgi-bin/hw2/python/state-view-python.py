#!/usr/bin/python3
import os, json

SESS_DIR = "/tmp/python_sessions"
COOKIE_NAME = "sid"

# Parse cookies from HTTP_COOKIE environment variable
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

# Try to load session data
data = {}
if session_id:
    path = os.path.join(SESS_DIR, f"{session_id}.json")
    if os.path.exists(path):
        with open(path, "r", encoding="utf-8") as f:
            data = json.load(f)

# Redirect if no data found
if not data or "Name" not in data:
    print("Status: 302 Found")
    print("Location: /state-collect-python.html")
    print()
    exit()

# Send CGI headers
print("Content-Type: text/html")
print()

# HTML output
print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("  <meta charset='UTF-8'>")
print("  <meta name='viewport' content='width=device-width, initial-scale=1.0'>")
print("  <title>View Saved State -- Python</title>")
print("  <style>")
print("    body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; }")
print("    h1 { color: #333; text-align: center; border-bottom: 3px solid #007bff; padding-bottom: 10px; }")
print("    h2 { color: #555; margin-top: 30px; }")
print("    ul { background: #f8f9fa; padding: 20px 40px; border-left: 4px solid #28a745; list-style: none; }")
print("    li { padding: 8px 0; border-bottom: 1px solid #dee2e6; }")
print("    li:last-child { border-bottom: none; }")
print("    strong { color: #007bff; }")
print("    button { margin-top: 20px; padding: 10px 20px; background: #dc3545; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }")
print("    button:hover { background: #c82333; }")
print("  </style>")
print("</head>")
print("<body>")
print("  <h1>View Saved State -- Python</h1>")
print("  <p>This page displays the data saved in the session by the State Save Python CGI Script</p>")
print("  <h2>Saved Data:</h2>")
print("  <ul>")
print(f"    <li><strong>Name:</strong> {data.get('Name', 'N/A')}</li>")
print(f"    <li><strong>Favorite CSE Class:</strong> {data.get('Favorite_CSE_Class', 'N/A')}</li>")
print(f"    <li><strong>Graduation Year:</strong> {data.get('Graduation_Year', 'N/A')}</li>")
print("  </ul>")
print("  <form method='POST' action='/cgi-bin/hw2/python/state-clear-python.py'>")
print("    <button type='submit'>Clear Saved State</button>")
print("  </form>")
print("</body>")
print("</html>")