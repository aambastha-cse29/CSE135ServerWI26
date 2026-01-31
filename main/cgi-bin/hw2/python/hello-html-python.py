#!/usr/bin/python3

import os
from datetime import datetime

print("Cache-Control: no-cache")
print("Content-Type: text/html")

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Hello HTML World - Python</title>")
print("</head>")
print("<body>")
print("<h1 align='center'>Hello HTML World</h1><hr/>")
print("<p>Hello World</p>")
print("<p>This page was generated with the Python programming language</p>")

today = datetime.now()
IP = os.environ.get('REMOTE_ADDR')

print("<p> Today is: {}</p>".format(today.strftime("%Y-%m-%d %H:%M:%S")))
print("<p> Your IP address is: {}</p>".format(IP))
print("<p>Using the Common Gateway Interface (CGI) protocol - CSE 135 Team Aman Ambastha</p>")
print("</body>")
print("</html>")
