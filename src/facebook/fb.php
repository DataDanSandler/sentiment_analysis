<?php
/**
* This is sample subscription endpoint for using Facebook real-time update
* See http://developers.facebook.com/docs/api/realtime to additional
* documentation
*/
// DPS - new JSON HTTP post function
//from http://www.nonhostile.com/howto-http-post-json-using-php.asp
//


  function JsonPost($url, $json) {
      $contentLength = strlen($json);
      $headers = "Content-type: text/json\r\nContent-Length: {$contentLength}\r\n";

      // populate the options,
      $options = array(
         'http' => array(
         'method' => 'POST',
            'header' => $headers,
            'ignore_errors' => true,
            'content' => $json
         )
      );

      // create context
      $context = stream_context_create($options);
      $fp = @fopen($url, 'r', false, $context);
         // get the response and decode
         $resp = stream_get_contents($fp);
         $resp = json_decode($resp, true);

         // close the response
         fclose($fp);
      return $resp;
 }

function GetFacebookFeed($json_in, $json_dt, $json_page_id, $json_com_id, $json_par_id, $json_pst_id, $json_sndr_id) {
ini_set('auto_detect_line_endings', TRUE);


  $FBBin = "<APP_HOME>/facebook/php-sdk/facebook-php-sdk-master";
  $ini_file = "<APP_HOME>/facebook/conf/facebook.ini";
  $ini_array = parse_ini_file($ini_file);

  require $FBBin . '/src/facebook.php';


  $FBAppID = $ini_array['app_id'];
  $FBAppSecret = $ini_array['app_secret'];

  $FBUserFields = $ini_array['user_fields'];
  $FBPageFields = $ini_array['page_field'];

  $config = array(
    "appId" => $FBAppID,
    "secret" => $FBAppSecret);

  $facebook = new Facebook($config);
  $token = $facebook->getAccessToken();
  $FBAccessToken = $token;
  $facebook->setAccessToken($FBAccessToken);

  $fbDateStr = $json_dt;
  $fbPageId = $json_page_id;
  if ($json_pst_id) {
      $api_str = "/" . $json_pst_id;
  } elseif ($json_par_id) {
      $api_str = "/" . $json_par_id;
  } elseif ($json_com_id) {
      $api_str = "/" . $json_com_id;
  } else {
      $api_str = "/" . $json_page_id . "/feed?limit=1&offset=0&until=" . $fbDateStr;
  }
  $updates = json_encode($facebook->api($api_str));
//
// backfill optoinal structures
//
   $js_dec_str = json_decode($updates , true);
   $js_arr_new = json_decode($updates , true);
   $json_comment_cnt = 0;
   $json_comment_cnt = $js_dec_str['comments']['count'];
   if ($json_comment_cnt <= 0) {
        $js_arr_new['comments']['data'] = array();
   }
   $json_likes_cnt = 0;
   $json_likes_cnt = $js_dec_str['likes']['count'];
   if ($json_likes_cnt <= 0) {
        $js_arr_new['likes']['data'] = array();
   }
   $updates = json_encode($js_arr_new);


  if (!$json_sndr_id) {
      $json_sndr_id = $json_page_id;
  }
  if ($json_sndr_id) {
      $api_root = "/" . $json_sndr_id . "?fields=";
      $api_sender_user = $api_root . $FBUserFields;
      $api_sender_page = $api_root  . $FBPageFields;
      $json_sender_user = $facebook->api($api_sender_user);
      $json_sender_page = $facebook->api($api_sender_page);
      $json_sender = addslashes(json_encode(array_merge($json_sender_user, $json_sender_page)));
   }
   define('FLUME_HTTP_PROXY', 'http://' . gethostname() . '.ec2.internal:51400/');
   $json_str = addslashes($updates);
   date_default_timezone_set('EST');
   $date = new DateTime();
   $dateStr = print_r($date->getTimestamp(), true);

   $jarr = addslashes(print_r(array('comment_id' => $json_com_id, 'post_id' => $json_pst_id, 'parent_id' => $json_par_id, 'fbdate' => $json_dt, 'page_id' => $json_page_id , 'sender_id' => $json_sndr_id , 'token' => $token), true));

   $json_flume = array('fb_post' => $json_in
                      ,'fb_message' => $json_str
                      ,'fb_sender' => $json_sender
                    //,'fb_debug' => $jarr
                 );
   $json_in = '\"fb_post\":' . $json_in;

   $json_str = ',\"fb_message\":' . $json_str;
   $json_str = str_replace("\\\"data\\\":", "\\\"data_fb\\\":", str_replace("\\\"from\\\":", "\\\"from_fb\\\":", $json_str));



   $json_sender = ',\"fb_sender\":' . $json_sender;
   $json_sender = str_replace("\\\"data\\\":", "\\\"data_fb\\\":", str_replace("\\\"from\\\":", "\\\"from_fb\\\":", $json_sender));
   $jarr = ',\"fb_debug\":' . $jarr;
   $json_fld = '[{"headers" : {"host" : FLUME_HTTP_PROXY, "timestamp":"' . $dateStr . '"},"body": "{' . $json_in . $json_str .  $json_sender . '}"}]';
   // use the following for debugging
   //$json_fld = '[{"headers" : {"host" : FLUME_HTTP_PROXY, "timestamp":"' . $dateStr . '"},"body": "{' . $json_in . $json_str .  $json_sender . $jarr . '}"}]';
   JsonPost(FLUME_HTTP_PROXY, $json_fld);

  // Replace with your own code here to handle the update
  // Note the request must complete within 15 seconds.
  // Otherwise Facebook server will consider it a timeout and
  // resend the push notification again.
}


// Please make sure to REPLACE the value of VERIFY_TOKEN 'abc' with
// your own secret string. This is the value to pass to Facebook
// when add/modify this subscription.
$inifile = "/home/cloudera/facebook/conf/facebook.ini";
$iniarray = parse_ini_file($inifile);
$ver_token = $iniarray['callback_passphrase'];
define('VERIFY_TOKEN', $ver_token);
$method = $_SERVER['REQUEST_METHOD'];
ini_set('auto_detect_line_endings', TRUE);

// In PHP, dots and spaces in query parameter names are converted to
// underscores automatically. So we need to check "hub_mode" instead
// of "hub.mode".
if ($method == 'GET' && $_GET['hub_mode'] == 'subscribe' &&
    $_GET['hub_verify_token'] == VERIFY_TOKEN) {
  echo $_GET['hub_challenge'];
} else if ($method == 'POST') {
  //$updates = json_decode(file_get_contents("php://input"), true);
  $js_in = "php://input";
  $js_dec = json_decode(file_get_contents($js_in), true);
  $updates = file_get_contents($js_in);

  $json_str = addslashes(print_r($updates, true));

   foreach ($js_dec['entry'] as $key => $value) {
      $json_time = $value['time'];
      $json_fb_page = $value['id'];
      foreach($value['changes'] as $key => $val) {
           $json_comment_id = $val['value']['comment_id'];
           $json_parent_id = $val['value']['parent_id'];
           $json_post_id = $val['value']['post_id'];
           $json_sender_id = $val['value']['sender_id'];
      }
   }

   GetFacebookFeed($json_str, $json_time, $json_fb_page, $json_comment_id, $json_parent_id, $json_post_id, $json_sender_id);

  // Replace with your own code here to handle the update
  // Note the request must complete within 15 seconds.
  // Otherwise Facebook server will consider it a timeout and
  // resend the push notification again.
}


?>

