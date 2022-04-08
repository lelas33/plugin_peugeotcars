#!/usr/bin/env python3
import sys
import os
import datetime

flog_mqtt = open("tlog_mqtt.log", "a")

str1 = "Test1"
str2 = "Test2"

now = datetime.datetime.now()

str_dt = now.strftime("%d/%m/%Y %H:%M:%S")
log_txt = "%s|%s|%s\n" % (str_dt, str1, str2)
flog_mqtt.write(log_txt)




