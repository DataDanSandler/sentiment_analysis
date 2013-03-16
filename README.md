How To setup a Facebook app that streams to Hadoop
===================

This repository contains sample code to create a FB app that streams data to Hadoop.  It includes components such as:

1) Sample PHP code for the Facebook HTTP gets and posts
2) Flume configuration for a Facebook HTTP Source
3) The flume agent
4) A INI file for the Facebook PHP code
5) DDL for a Facebook hive table 

Assumes you have working knowledge of these technical components.

Step 1: Install & Configure Apache
---------------
1) **Install apache**
   a) yum install httpd
   b) vi /etc/httpd/conf/httpd.conf
   c) Uncomment NameVirtualHost *:*
      <VirtualHost *:*>
         DocumentRoot /var/www/html
         ServerName www.example.com
      </VirtualHost>
   d) chkconfig httpd on
   e) /etc/init.d/httpd restart

2) **Install PHP**
   a) http:www.howtoforge.com/installing-apache2-with-php5-and-mysql-support-on-centos-5.3
   b) yum install php

3) **Install FB PHP**
   a) Mkdir /home/cloudera/facebook/php-sdk/facebook-phpsdk-master
   b) Download https:github.com/facebook/facebook-php-sdk
      i) wget https:github.com/facebook/facebook-php-sdk/archive/master.zip

Step 2: Configure flume
---------------
 1) See flume.conf for proper values
 1a) Make sure you have flume core and flume ng sdk 1.3.0 or greater
 2) Also need to download and add the folloiwng missing jars
		 1015  cp flume-ng-core-1.3.0.jar /usr/lib/flume-ng/lib/
		 1047  cp /home/cloudera/facebook/apache-flume-1.3.0-bin/lib/flume-ng-sdk-1.3.0.jar /usr/lib/flume-ng/lib
 3) NOTE - the YYYYMMDDHH isn�t working, disabled for now, need to try later
		a. Reason (excption=
		at java.lang.Thread.run(Thread.java:662)
		Caused by: java.lang.RuntimeException: Flume wasn't able to parse timestamp header in the event to resolve time based bucketing. Please check that you're correctly populating timestamp header (for example using TimestampInterceptor source interceptor).)
		b. need to specify Timestamp in Header as documented here http:flume.apache.org/FlumeUserGuide.html (See JSONHandler)
		c. Modified flume.conf to make use of inteceptors as documented in http:flume.apache.org/FlumeUserGuide.html
			i. Here are the new properties in flume.conf
		SocialAgent.sources.FacebookHttpSource.interceptors = Ts
		SocialAgent.sources.FacebookHttpSource.interceptors.Ts.type = org.apache.flume.interceptor.TimestampInterceptor$Builder

 4) Need to modify /etc/default/flume_ng_agent for a single Agent called SocialAgent, then /etc/flume-ng-agent/flume.conf to have Twitter and Facebook as a single aganet with difewrent channels and sinks
		a. Make sure two channe;s specified otherwise only the last channel specified will run
		b. e.g.
			SocialAgent.sources = FacebookHttpSource Twitter
			SocialAgent.channels = FBmemoryChannel MemChannel
			SocialAgent.sinks = fbHDFS HDFS


Step 3: Create and Configure FB App
---------------
1) create a Facebook App
2) create a real-time subscription for the FB app
3) Add the app to the page you care about
4) Setup a callback URL
5) Change the parameters in Facebook in facebook.ini according to your app and callback URL
      	a. To add an App as a tab to a Page:
	   	    b. www.facebook.com/add.php?api_key=YOUR_APP_ID&pages=1&page=YOUR_PAGE_ID


Step 4: Stop/Start flume agent
---------------
1) /etc/init.d/flume-ng-agent stop
2) /etc/init.d/flume-ng-agent start
3) make sure everything looks good in /var/log/flume-ng/flume.log


