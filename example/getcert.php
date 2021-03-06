<?php
header("Access-Control-Allow-Origin: http://83.212.86.157");  //change the header

//options start
$debug='off';     // on/off, if it works, change this value to 'off', so that it will not display debug messages anymore
$method=-1;       // -1/0/1/2/3,  choose a method to read the js file containing the certificate info, VALUES:  -1:try all methods,0:cURL,1:fopen,2:file_get_contents,3:http_get()
$projection=0;    // 0/1,   choose a method to show the badge   0:our implementation, 1:ODI implementation
$size=90;         // set the size of the badge and letters, default is 100
//options end

if($debug=='on') echo '<p style="color:green;">-php file loaded succesfully</p>';

$getype=0;
$ferror=0;
$raw='Bronze Open Data Certificate';
$pilot='Silver Open Data Certificate';
$standard='Gold Open Data Certificate';
$expert='Platinum Open Data Certificate';
$autom='automatically awarded';
$success="false";
ini_set("allow_url_fopen", true);

function fetchUrl($uri) {
    $handle = curl_init();

    curl_setopt($handle, CURLOPT_URL, $uri);
    curl_setopt($handle, CURLOPT_POST, false);
    curl_setopt($handle, CURLOPT_BINARYTRANSFER, false);
    curl_setopt($handle, CURLOPT_HEADER, true);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($handle);
    $hlength  = curl_getinfo($handle, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $body     = substr($response, $hlength);    

  // If HTTP response is not 200, throw exception
    if ($httpCode != 200) {
        throw new Exception($httpCode);
    }
    return $body;
}

function checkline($line) {
$tot=0;
if($line[0]=='true') $tot++;
if($line[1]=='true') $tot++;
if(substr($line[2],- 1 - strlen($_GET['ur'])) == '/' . $_GET['ur']) $tot++;
if($tot==3) {
   $tot2=0;    
   for($n=0;$n<200;$n++) {    
        if(substr($line[3],$n,1)=='/') {       
            $tot2++;
            if($tot2==5) $f=$n;
              else if($tot2==6) { $f2=$n; break;}
              }
           }
        return substr($line[3],$f,$f2-$f);
  }else return "false";
}

function check_state($state) {	
	if($state==0) $r_txt = '<p style="color:green; text-align:center;">-Debug returned state value 0:_normal_operation_</p>';
	   else if($state==1) $r_txt = '<p style="color:red; text-align:center;">-Debug returned state value 1:_not_able_to_load_certificate_information_</p>';
	       else if($state==2) $r_txt = '<p style="color:red; text-align:center;">-Debug returned state value 2:_not_able_to_load_the_csv_files_</p>';		   
        return $r_txt;
}

function f_name_odi($urlf) {

     $pos = explode('<span>', $urlf);
     $pos = explode('<\/span>', $pos[1]);
     return $pos[0];
}

function projection1($url, $nm) {     
    
    global $raw, $expert, $standard, $pilot, $size, $debug, $ferror;
    $bgurlf = $url;
    
    echo '<div class=\'open-data-certificate\'> <style>@import url(https://certificates.theodi.org/assets/badge.css);
</style><a href="https://certificates.theodi.org/en/datasets' . $nm . '/certificate">  <img alt="Badge" src="https://certificates.theodi.org/en/datasets' . $nm . '/certificate/badge.png" style="width:' . ($size-50) . '%;height:' . ($size-50) . '%;" /> </a><ul class=\'open-data-certificate-details\'> <li><span>'. f_name_odi($bgurlf) . '</span> </li> <li> <span><font style="font-size:' . $size . '%;text-align:left;">';
    $pos = strpos($bgurlf, $raw);
                     if($pos===false) {
                        $pos = strpos($bgurlf, $pilot);
                        if($pos===false)  {
                               $pos = strpos($bgurlf, $standard);
                               if($pos===false)  {
                                    $pos = strpos($bgurlf, $expert);
                                    if($pos===false)  {
                                       echo 'No Level Certificate'; 
                                       }else echo $expert;
                                    }else echo $standard;
                                }else echo $pilot;
                        }else echo $raw;

echo '</font></span> </li> <li><font style="font-size:' . $size . '%;"> <span>Active - automatically awarded</span></font> </li> </ul></div><br><br><br>';


if($debug=='on') echo check_state($ferror);
if($debug=='on') echo '<p style="color:grey;">-Debug is ON, if you don\'t want to see this text please change the value of the $debug variable to OFF.</p>';

}

for($i=1;$i<200;$i++) { 
   if($success=="true" || $ferror!=0) break;
   $ar = 'url' . $i;
   if($_GET[$ar]!='') {  
       $entries=0;
       $tmp = file_get_contents($_GET[$ar]);
       if ($tmp=='') { if($debug=='on') echo '<p style="color:red;">-check url path: ' . $_GET[$ar] . '</p>'; $ferror=2; break;}
           else if($debug=='on') echo '<p style="color:grey;">-Searching certificates in ' . $_GET[$ar];
       $res = explode("\n", $tmp);
       if($debug=='on') echo '(' . (count($res)-1) . '_entries)</p>';   
          for($j=0;$j<count($res);$j++) {
              if($debug=='on') $entries++;
              $res2 = explode(",", $res[$j]);
              $res3 = checkline($res2);      
              if($res3!="false") {
                   if($debug=='on') echo '<p style="color:green;"> -connection found in line #' . $j . '</p>';                
                   $jsurl= 'https://certificates.theodi.org/en/datasets' . $res3 . '/certificate/badge.js';                    
                   if($debug=='on') echo '<p style="color:grey;">----trying_methods_to_read_url:_' . $jsurl . '<br>';                
                   if($method!=-1) $getype=$method;
                   if($getype==0) {
                          if($debug=='on')   echo '<p style="color:grey;"> -trying method 0..</p>'; 
                          try{              
                                $bgurl = fetchUrl($jsurl);
                                }catch(Exception $e) {
                                        if ($bgurl=='') {
					     if($debug=='on') echo '<p style="color:red;"> failed to open file ' . '</p>'; 
                                             if($method==-1) $getype=1;
									    	  }
                                         }
                    } 

                   if($getype==1) {
                          if($debug=='on') echo '<p style="color:grey;"> -trying method 1..'; 
                          if (function_exists('fopen') && function_exists('stream_get_contents') && function_exists('fclose')) {
                                 $stream=fopen($jsurl,'r');
                                 $bgurl = stream_get_contents($stream);
                                 fclose($stream);
	                             }

                          if ($bgurl=='') {
		                      if($debug=='on') echo '<p style="color:red;"> failed to open file ' . '</p>'; 
                              if($method==-1) $getype=2;
		                      }
                    }

                   if($getype==2) {
                          if($debug=='on') echo '<p style="color:grey;"> -trying method 2..';
                          if (function_exists('file_get_contents')) $bgurl = file_get_contents($jsurl);
                          if ($bgurl=='') {
	                      	 if($debug=='on') echo '<p style="color:red;"> failed to open file ' . '</p>'; 
                             if($method==-1) $getype=3;
	                       	 }
                    }

                   if($getype==3) {
                         if($debug=='on') echo '<p style="color:grey;"> -trying method 3..';
                         if(function_exists('http_get')) $bgurl = http_get($jsurl, array("timeout"=>1), $info);
                         if($bgurl=='') {
	                    	 if($debug=='on') echo '<p style="color:red;"> failed to open file ' . '</p>'; 
                             if($method==-1) $getype=0;
		                     }
                    }                  

                    if ($bgurl=='') {if($debug=='on') echo '<p style="color:red;">-dataset #' . $res3 . ' No method could read certificate info, Possible reasons: <br> i) It does not exist(check your csv file)<br> ii) Open Data Server is down<br> iii) A different method is needed<br> iv) Enable extension=php_http.dll or extension=php_curl.dll in your php.ini file </p>'; $ferror=1;} else if($debug=='on') echo '<p style="color:green;">-Certificate info loaded</p>';
                    if($projection==1) {
                                 projection1($bgurl, $res3);
                                 return;
                                 }
                    echo '<p style="text-align:center;"><a href="https://certificates.theodi.org/en/datasets' . $res3 . '/certificate" target="_blank"><img style="width:' . ($size-30) . '%;height:' . ($size-30) . '%;" src="' . 'https://certificates.theodi.org/en/datasets' . $res3 . '/certificate/badge.png"></a>'; 
                    $res4=$res3;
                    $tmp2 = $bgurl;                  
                    $success="true";
                    break;
                }
            }   
    }else break;
}

if($success=="false" || $ferror!=0) {
	     echo '<p></p>'; 
		 if($debug=='on') echo '<p style="color:red;">Certificate info could not be retrieved</p>';
		 } else {    
                  echo '<br><a href="https://certificates.theodi.org/en/datasets' . $res4 . '/certificate" target="_blank"><table style="border-collapse: collapse;"><tr><p style="text-align:center;font-size:' . $size . '%; line-height: ' . ($size + 50) . '%;">';    
                     $pos = strpos($tmp2, $raw);
                     if($pos===false) {
                        $pos = strpos($tmp2, $pilot);
                        if($pos===false)  {
                               $pos = strpos($tmp2, $standard);
                               if($pos===false)  {
                                    $pos = strpos($tmp2, $expert);
                                    if($pos===false)  {
                                       echo 'No Level Certificate'; 
                                       }else echo $expert;
                                    }else echo $standard;
                                }else echo $pilot;
                        }else echo $raw;

                  $pos = strpos($tmp2, $autom);
                  if($pos===false) { 
				      echo '</p></tr><tr><p style="text-align:center; font-size:' . ($size-10) . '%; line-height: ' . ($size+50) . '%;">self certified</p></tr></table>'; 
					  } else echo '</p></tr><tr><p style="text-align:center; font-size:' . ($size-10) . '%; line-height: ' . ($size+50) . '%;">' . $autom . '</p></tr></table>';
                  echo '</a></p>';
}

if($debug=='on') echo check_state($ferror);
if($debug=='on') echo '<p style="color:grey;">-Debug is ON, if you don\'t want to see this text please change the value of the $debug variable to OFF.</p>';

?>