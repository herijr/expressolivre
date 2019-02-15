<?php
	class Functions{

		// get the offset against GMT.
		function CalculateDateOffset( $date = 'now' )
		{
			$tz = new DateTimeZone($_SESSION['phpgw_info']['user']['preferences']['expressoMail']['timezone']);
			$gmt = new DateTimeZone("Etc/GMT+0");
			$gmttime = new DateTime($date, $gmt);
			$offset = $tz->getOffset($gmttime);
			return $offset;
		}

		function getTimezones()
		{
			$zones = timezone_identifiers_list();
			$friendly_zones = array();
			foreach ($zones as $zone) {
				if (preg_match("/^(Africa|America|Antarctica|Arctic|Asia|Atlantic|Australia|Europe|Indian|Pacific)\/.*$/", $zone))
					array_push($friendly_zones,  $zone);
			}
			return $friendly_zones;
		}

		function CallVoipConnect($params){
			$fromNumber = $_SESSION['phpgw_info']['user']['preferences']['expressoMail']['telephone_number'];
			if(!$fromNumber)
				return false;
			$toNumber	= $params['to'];
			
			$voipServer	= $_SESSION['phpgw_info']['expressomail']['server']['voip_server'];
			$voipUrl	= $_SESSION['phpgw_info']['expressomail']['server']['voip_url'];
			$voipPort	= $_SESSION['phpgw_info']['expressomail']['server']['voip_port'];
				
			if(!$voipServer || !$voipUrl || !$voipPort)
				return false;
			$url		= "http://".$voipServer.":".$voipPort.$voipUrl."?magic=1333&acao=liga&ramal=".$fromNumber."&numero=".$toNumber;			
			$sMethod = 'GET ';
            $crlf = "\r\n";
            $sRequest = " HTTP/1.1" . $crlf;
            $sRequest .= "Host: localhost" . $crlf;
            $sRequest .= "Accept: */* " . $crlf;
            $sRequest .= "Connection: Close" . $crlf . $crlf;            
            $sRequest = $sMethod . $url . $sRequest;    
            $sockHttp = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);            
            if (!$sockHttp)  {
                return false;
            }
            $resSocketConnect = socket_connect($sockHttp, $voipServer, $voipPort);
            if (!$resSocketConnect) {
                return false;
            }
            $resSocketWrite = socket_write($sockHttp, $sRequest, strlen($sRequest));
            if (!$resSocketWrite) {
                return false;
            }    
            $sResponse = '';    
            while ($sRead = socket_read($sockHttp, 512)) {
                $sResponse .= $sRead;
            }            
            
            socket_close($sockHttp);            
            $pos = strpos($sResponse, $crlf . $crlf);
            return substr($sResponse, $pos + 2 * strlen($crlf));									
		}

		function getDirContents($dir){
			//$dir = dirname($dir);
	
		   	if (!is_dir($dir)) 	{
		   			return 'Error: Cannot load files in :'. $dir.' !';
		   	}
		   	
		   	if ($root=@opendir($dir))	{
		       
		       while ($file=readdir($root))	{	           
		           if($file!="." && $file!="..")
			           $files[]=$dir."/".$file;           
		       }
		       
		   	}
		   	
		   return $files;
		}
				
		function getFilesJs($includeFiles = '', $update_version = ''){
			
			$files = $this -> getDirContents('js');
			$str_files = '';
			
			if($includeFiles) {
				$includeFiles = explode(",",trim($includeFiles));
				// Bug fixed for array_search function
				$includeFiles[count($includeFiles)] = $includeFiles[0];
				$includeFiles[0] = null;
				// End Bug fixed.
			}														
			for($i = 0; $i < count($files); $i++) {				
				if(count(explode('.js',$files[$i])) > 1) {
					if($includeFiles  && array_search(trim($files[$i]),$includeFiles)){	
						$str_files .= "<script src='".$files[$i]."?".$update_version."' type='text/javascript'></script>";						
					}
				}
			}
			
			return $str_files;
		}								

		function getReturnExecuteForm(){
			if(isset($_SESSION['response'])){
				$response = $_SESSION['response'];
				$_SESSION['response'] = null;
				return $response;
			}
		}
		function getLang($key){
			if ( isset( $_SESSION['phpgw_info']['expressomail']['lang'][$key] ) )
				return $_SESSION['phpgw_info']['expressomail']['lang'][$key];			
			else
				return ($key . '*');
		}

		function get_preferences() {
			$result = $_SESSION['phpgw_info']['user']['preferences']['expressoMail'];
			if ( $_SESSION['phpgw_info']['expressomail']['email_server']['defaultUsersignature'] ) {
				$result['auto_signature'] = true;
				$result['signature']      = $_SESSION['phpgw_info']['expressomail']['email_server']['defaultUsersignature'];
				$result['type_signature'] = 'html';
			}
			return $result;
		}
		// Unicode Conversor: convert everything from UTF-8 into an NCR[Numeric Character Reference]
		function utf8_to_ncr($content)	{
            $result = "";            
            while ($strlen = mb_strlen($content)) {
                $c = mb_substr($content, 0, 1, "UTF-8");
            	$h = ord($c{0});   
	            if ($h <= 0x7F || $h < 0xC2) {
			    // fixing curly brackets 
			    if($h == 0x7B || $h == 0x7D) 
				    $result .= "&#" . $h . ";"; 
			    else 
				    $result .= $c;
		    }
	           	else if ($h <= 0xDF) {
	                $h = ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
	                $result .= "&#" . $h . ";";
	            } else if ($h <= 0xEF) {
	                $h = ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6 | (ord($c{2}) & 0x3F);
	                $result .= "&#" . $h . ";";
	            } else if ($h <= 0xF4) {
	                $h = ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12 | (ord($c{2}) & 0x3F) << 6 | (ord($c{3}) & 0x3F);
	                $result .= "&#" . $h . ";";
	            }                
                $content = mb_substr( $content, 1, $strlen, "UTF-8");
            }
            return mb_convert_encoding($result,"UTF-8");
        }
    }	
?>
