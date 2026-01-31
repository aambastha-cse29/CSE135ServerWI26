#!/usr/bin/python3

import os

print("Cache-Control: no-cache")
print("Content-Type: text/html")

print()

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("<title>Environment Variables - Python</title>")
print("</head>")
print("<body>")
print("<h1 align='center'>Environment Variables - Python</h1><hr/>")
print("<p>This Page Displays All Environment Variables Available To This CGI Script</p>")
print("<table border='1'>")
print("<tr><th>Variable Name</th><th>Value</th></tr>

for key, value in os.environ.items():
    print("<tr><td>{}</td><td>{}</td></tr>".format(key, value))

print("</table>")
print("<p>Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha</p>")
print("</body>")
print("</html>")