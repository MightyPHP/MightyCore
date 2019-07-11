<?php
class REDIRECT{

	public function go($link){
		if($this->isHttps()){
			$url = "https://".$link;
		}else{
			$url = "http://".$link;
		}
		/*$ch = curl_init();
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
		curl_setopt ($ch, CURLOPT_REFERER, $url);
		$header = array($config['tokenization']['client_name'].":".$_REQUEST['token'], 'security-action: redirect');
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 
		
		$result = curl_exec($ch);
		if(curl_errno($ch)){
		    die('Curl error: ' . curl_error($ch));
		}
		curl_close($ch); */
		echo '<script>window.location.href="'.$url.'"</script>';
		//exit;
	}
	
	private function isHttps(){
		if (array_key_exists("HTTPS", $_SERVER) && 'on' === $_SERVER["HTTPS"]) {
			return true;
		}
		if (array_key_exists("SERVER_PORT", $_SERVER) && 443 === (int)$_SERVER["SERVER_PORT"]) {
			return true;
		}
		if (array_key_exists("HTTP_X_FORWARDED_SSL", $_SERVER) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]) {
			return true;
		}
		if (array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && 'https' === $_SERVER["HTTP_X_FORWARDED_PROTO"]) {
			return true;
		}
		return false;
	}
}