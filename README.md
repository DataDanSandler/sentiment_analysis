How To setup a Facebook app that streams to Hadoop & Make Twitter stream real-time
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
   <pre>
      <VirtualHost *:*>
         DocumentRoot /var/www/html
         ServerName www.example.com
      </VirtualHost>
      </pre>
      
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
 
		 cp flume-ng-core-1.3.0.jar /usr/lib/flume-ng/lib/
		 
		 cp /home/cloudera/facebook/apache-flume-1.3.0-bin/lib/flume-ng-sdk-1.3.0.jar /usr/lib/flume-ng/lib
		 
 3) NOTE - the YYYYMMDDHH isn�t working, disabled for now, need to try later
 
		a. Reason (excption=t java.lang.Thread.run(Thread.java:662)
		Caused by: java.lang.RuntimeException: Flume wasn't able to parse timestamp header in the event to resolve time based bucketing. Please check that you're correctly populating timestamp header (for example using TimestampInterceptor source interceptor).)
		
		b. need to specify Timestamp in Header as documented here http:flume.apache.org/FlumeUserGuide.html (See JSONHandler)
		
		c. Modified flume.conf to make use of inteceptors as documented in http:flume.apache.org/FlumeUserGuide.html
		   
		   i. Here are the new properties in flume.conf
		<pre>
		SocialAgent.sources.FacebookHttpSource.interceptors = Ts
		SocialAgent.sources.FacebookHttpSource.interceptors.Ts.type = org.apache.flume.interceptor.TimestampInterceptor$Builder
		</pre>

 4) Need to modify /etc/default/flume_ng_agent for a single Agent called SocialAgent, then /etc/flume-ng-agent/flume.conf to have Twitter and Facebook as a single aganet with difewrent channels and sinks
 
		a. Make sure two channe;s specified otherwise only the last channel specified will run

                b. e.g.
                   <pre>
                   SocialAgent.sources = FacebookHttpSource Twitter
		   SocialAgent.channels = FBmemoryChannel MemChannel
	           SocialAgent.sinks = fbHDFS HDFS
		   </pre>


Step 3: Create and Configure FB App
---------------
1) create a Facebook App https://developers.facebook.com/docs/guides/canvas/

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


Step 5: Make Twitter stream real-time
---------------
1) Create custom Java Package to Ignore Flume Temp Files
You’ll want to create a new Java package using the following steps. There is no Java programming knowledge required, simply follow these instructions.
It is necessary to create this Java class and JAR it so that you can exclude the temporary Flume files created as Tweets are streamed to HDFS

<pre>
mkdir com
mkdir com/twitter
mkdir com/twitter/util
export CLASSPATH=/usr/lib/hadoop/hadoop-common-2.0.0-cdh4.1.3.jar:hadoop-common.jar
Be sure to reference the cdh4.X.X you are working with
vi com/twitter/util/FileFilterExcludeTmpFiles.java
Copy the Java source code at the end of the posting and save it.
javac com/twitter/util/FileFilterExcludeTmpFiles.java
jar cf TwitterUtil.jar com
cp TwitterUtil.jar /usr/lib/hadoop
</pre>

2) - Remove Wait condition from Oozie job configuration 

Open coord-app.xml (in the location you placed the oozie-workflows folder)

Remove the following tags. This is extremely important in making the tutorial as real-time as possible. The default Oozie workflow has defined a readyIndicator which acts as a wait event. It instructs the workflow to create a new partition after an hour completes. Thus if you leave this configuration as-is, there will be a lag as great as one-hour between tweets and when the tweets can be queried. The reason for this default configuration is that the tutorial did not define the custom JAR we built and deployed for Hive that instructs MapReduce to omit temporary Flume files. Because we have deployed this custom package in step 1, we do not have to force a full hour to complete before querying tweets.

<pre>
<data-in name="readyIndicator" dataset="tweets">
  <!-- I’ve done something here that is a little bit of a hack. Since Flume doesn’t have a good mechanism for notifying an application of when it has rolled to a new directory, we can just use the next directory as an input event, which instructs Oozie not to kick off a coordinator action until the next dataset starts being available. -->
  <instance>${coord:current(1 + (coord:tzOffset() / 60))}</ instance>
</data-in
</pre>


Retart Oozie workflow

<pre>
sudo -u hdfs oozie job -oozie http://localhost:11000/oozie -config /home/oozie_lib/oozie-workflows/job.properties -run
sudo -u hdfs oozie job -oozie http://localhost:11000/oozie -kill  <oozie_coordinating_job_name>
</pre>


3) Modify the Hive Configuration File to use Packge in Step 1 to Ignore Flume Temp files

Edit the file /etc/hive/conf/hive-site.xml, and add the following tags.  The first property ensures that you won’t have to add the JSON SerDe package and the new customer package that excludes Flume temporary files for each Hive session. This will become part of the overall Hive configurations that is available to each Hive session. The second tags instruct MapReduce of the class name and location of the new Java class that we created and compiled above.

<pre>
<property>
  <name>hive.aux.jars.path</name>
  <value>file:///usr/lib/hadoop/hive-serdes-1.0-SNAPSHOT.jar,file:///usr/lib/hadoop/TwitterUtil.jar</value>
  </property>
 <property>
   <value>com.twitter.util.FileFilterExcludeTmpFiles</value>
 </property>
 </pre>
Sample Code

Use the following 12-lines of Java code (many thanks to the contributors to the CDH Google group for the working example)

<pre>
package com.twitter.util;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.fs.PathFilter;
public class FileFilterExcludeTmpFiles implements PathFilter {
public boolean accept(Path p) {
String name = p.getName();
return !name.startsWith("_") && !name.startsWith(".") && !name.endsWith(". tmp");
}
}
</pre>
- See more at: http://www.datadansandler.com/2013/03/making-clouderas-twitter-stream-real.html#sthash.wBNmhsQU.dpuf
