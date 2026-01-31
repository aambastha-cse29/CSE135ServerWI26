#!/usr/bin/python3

import os
from datetime import datetime
import json

print("Cache-Control: no-cache")
print("Content-Type: application/json")
print()

data = {}
data["GREETING"] = "Hello JSON World -- Greetings From Aman Ambastha"
data["MESSAGE"] = "This Page Was Generated With The Python Programming Language"
today = datetime.now()
data["DATE (UTC)"] = today.strftime("%Y-%m-%d %H:%M:%S")
IP = os.environ.get('REMOTE_ADDR')
data["IP_ADDRESS"] = IP
data["NOTE"] = "Using The Common Gateway Interface (CGI) Protocol - CSE 135 Team Aman Ambastha" 

print(json.dumps(data))

