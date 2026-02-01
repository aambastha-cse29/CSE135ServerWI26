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

# Delete session file

if session_id:
    path = os.path.join(SESS_DIR, "f{session_id}.json")
    if os.path.exists(path):
        os.remove(path)



# Send expired cookie to delete it from browser
print("Set-Cookie: " + f"{COOKIE_NAME}=deleted; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; HttpOnly; SameSite=Lax; Secure")
print("Status: 302 Found")
print("Location: /state-collect-python.html")
print()