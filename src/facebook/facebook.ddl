drop table facebook;

CREATE EXTERNAL TABLE facebook (

fb_post STRUCT<
  object:STRING,
  entry:ARRAY<STRUCT<
        id:STRING,
        time:INT,
        changes:ARRAY<STRUCT<
           field:STRING,
           value:STRUCT<
                 item:STRING,
                 created_time:INT,
                 verb:STRING,
                 comment_id:STRING,
                 parent_id:STRING,
                 sender_id:BIGINT
           >           
        >>
      >>
>,
fb_message STRUCT<
   id:STRING ,
   message:STRING,
   picture:STRING,
   link:STRING,
   source:STRING,
   name:STRING,
   caption:STRING,
   description:STRING,
   icon:STRING,
   type:STRING,
   status_type:STRING,
   created_time:STRING,
   updated_time:STRING,
   privacy:STRUCT<
   	value:STRING
   >
, from_fb:STRUCT<
   	category:STRING,
   	   	name:STRING,
   	   	id:STRING
   >
, likes:STRUCT<
       data_fb:ARRAY<STRUCT<
    	        name:STRING,
   	        id:STRING
 	 >>,
 	 count:INT
 >
,    comments:STRUCT<
   	data_fb:ARRAY<STRUCT<
   	     id:STRING,
   	     from_fb:STRUCT<
   	          category:STRING,
   	          name:STRING,
   	          id:STRING
   	     >,
   	message:STRING,
   	created_time:STRING,
   	count:INT,
   	likes:INT
	   	>>
    >
  
>,
fb_sender STRUCT<
  has_added_app:BOOLEAN,
  is_published:BOOLEAN,
  talking_about_count:INT,
  were_here_count:INT,
  category:STRING,
  id:STRING,
  name:STRING,
  first_name:STRING,
  last_name:STRING,
  username:STRING,
  gender:STRING,
  locale:STRING,
  link:STRING,
  likes:INT
>
)
ROW FORMAT SERDE 'com.cloudera.hive.serde.JSONSerDe'
LOCATION '<HDFS_PATH>';

