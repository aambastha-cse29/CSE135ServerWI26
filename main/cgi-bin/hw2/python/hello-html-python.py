#!/usr/bin/python3

import os
from datetime import datetime

print("Cache-Control: no-cache")
print("Content-Type: text/html")
print()

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Hello HTML World - Python</title>")
print("</head>")
print("<body>")
print("<h1 align='center'>Hello HTML World -- Greetings From Aman Ambastha</h1><hr/>")
print("<p>Hello World</p>")
print("<p>This Page Was Generated With The Python Programming Language</p>")

today = datetime.now()
IP = os.environ.get('REMOTE_ADDR')

print("<p> Today Is: {}</p>".format(today.strftime("%Y-%m-%d %H:%M:%S")))
print("<p> Your IP Address Is: {}</p>".format(IP))
print("<p>Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha</p>")
print("</body>")
print("</html>")
