package com.twitter.util;
import java.io.IOException;
import java.util.ArrayList;
import java.util.List;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.fs.PathFilter;
/*
   Credit where credit due:
   https://groups.google.com/a/cloudera.org/forum/?fromgroups=#!topic/cdh-user/FWH80lehYxk
*/
public class FileFilterExcludeTmpFiles implements PathFilter {
public boolean accept(Path p) {
String name = p.getName();
return !name.startsWith("_") && !name.startsWith(".") && !name.endsWith(". tmp");
}
}