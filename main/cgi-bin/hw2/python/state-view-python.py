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
print("<head><title>View Saved State - Python</title></head>")
print("<body>")
print("<h1 align='center'>View Saved State - Python</h1><hr/>")
print("<p>This Page Displays The Data Saved In The Session By The State Save Python CGI Script</p>")
print("<h2>Saved Data:</h2>")
print("<ul>")
print(f"<li><strong>Name:</strong> {data.get('Name', 'N/A')}</li>")
print(f"<li><strong>University:</strong> {data.get('University', 'N/A')}</li>")
print(f"<li><strong>Department:</strong> {data.get('Department', 'N/A')}</li>")
print("</ul>")
print("<form method='POST' action='/cgi-bin/hw2/python/state-clear-python.py'>")
print("<button type='submit'>Clear Saved State</button>")
print("</form>")
print("</body>")
print("</html>")