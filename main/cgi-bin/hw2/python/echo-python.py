#!/usr/bin/python3

import os
from datetime import datetime
import sys

print("Cache-Control: no-cache")
print("Content-Type: text/html")
print()

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Echo - Python</title>")
print("</head>")
print("<body>")
print("<h1 align='center'>Echo - Python</h1><hr/>")
print("<p>This Page Echoes Back The Data Sent To This CGI Script/p>")

# Print Key Headers
print("<h2>Key Headers:</h2>")
print("<ul>")
print("<li><strong>REQUEST_METHOD:</strong> {}</li>".format(os.environ.get('REQUEST_METHOD', 'N/A')))
print("<li><strong>IP_ADDRESS:</strong> {}</li>".format(os.environ.get('REMOTE_ADDR', 'N/A')))
print("<li><strong>USER_AGENT:</strong> {}</li>".format(os.environ.get('HTTP_USER_AGENT', 'N/A')))
print("<li><strong>HOST:</strong> {}</li>".format(os.environ.get('HTTP_HOST', 'N/A')))
print("</ul>")


# Print Date and Time
print("<h2>Date and Time:</h2>")
today = datetime.utcnow()
print("<p> Page Generated At (UTC): {}</p>".format(today.strftime("%Y-%m-%d %H:%M:%S")))

# Retrieve the query string from the environment variable
print("<h2>Query String:</h2>")
query_string = os.environ.get('QUERY_STRING')
if query_string:
    # Parse the query string into key-value pairs
    params = query_string.split('&')
    print("<ul>")
    for param in params:
        key_value = param.split('=')
        if len(key_value) == 2:
            key = key_value[0]
            value = key_value[1]
            print(f"<li><strong>{key}:</strong> {value}</li>")
    print("</ul>")


# Message Body
print("<h2>Message Body:</h2>")
content_length = os.environ.get('CONTENT_LENGTH')
if content_length:
    try:
        length = int(content_length)
        message_body = sys.stdin.read(length)
        print("<pre>{}</pre>".format(message_body))
    except ValueError:
        print("<p>No Message Body Received.</p>")
else:
    print("<p>No Message Body Received.</p>")

print("</body>")
print("</html>")



