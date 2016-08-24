<?php

class facebookDebug {
	private $data;
	private $url;
        private $timeout;
        
	function __construct($url,$timeout=10) {
            $this->url=rawurlencode($url);
            $this->timeout=$timeout;
        }
	
	/* Collect share count from all available networks */
	public function _init() {
		$this->data = new stdClass;
                $this->data->total = 0;
                $data = $this->getSharesALL();
		return $data;
	}
        
        
      /* Build the multi_curl() crawler for all networks
       * 
       * @returns
       */  
      public function getHeader() {
          global $mashsb_options;
                $fb_mode = isset($mashsb_options['facebook_count_mode'])  ? $mashsb_options['facebook_count_mode'] : '';  

                $post_data = null;
                $headers = null;
                
                $options = array(
                CURLOPT_SSL_VERIFYPEER => FALSE,
                CURLOPT_SSL_VERIFYHOST => FALSE,
                //CURLOPT_USERAGENT, 'MashEngine v.1.1'
                );
				
                $RollingCurlX = new RollingCurlX(8);    // max 10 simultaneous downloads
		$RollingCurlX->setOptions($options);
                switch ($fb_mode){
                    case $fb_mode === 'likes':
                        $RollingCurlX->addRequest("http://graph.facebook.com/?id=" . $this->url, $post_data, array($this, 'getCount'), array('facebook_likes'), $headers);
                        break;
                    case $fb_mode === 'total':    
                        $RollingCurlX->addRequest("http://graph.facebook.com/?id=" . $this->url, $post_data, array($this, 'getCount'), array('facebook_total'), $headers);
                        break;
                    default:
                        $RollingCurlX->addRequest("http://graph.facebook.com/?id=" . $this->url, $post_data, array($this, 'getCount'), array('facebook_shares'), $headers);
                }
		$RollingCurlX->execute();
                
                $data = json_encode($this->data); // This return an json string instead
                //$data = $this->data;
                
                // return the total count
		//return $data->shares->total;
		return $data;
	}  
        
      /* 
       * Callback function to get share counts 
       */
        
         function getCount($data, $url, $request_info, $service, $time){
		$count = 0;
		if ($data) {
			switch($service[0]) {
			case "facebook_likes":
				$data = json_decode($data, true); 
                                $count = $data;
				//$count = (is_array($data) ? $data["share"]->share_count : $data->share_count);
				break;
                        case "facebook_shares":
				$data = json_decode($data, true); // return assoc array
                                $count = $data;
				break;
                        case "facebook_total":
				$data = json_decode($data, true); 
                                $count = $data;
				break;
			}
                       
			$count = (int) $count;
			/*$this->data->shares->total += $count;
                        $this->data->shares->$service[0] = $count;
                         * */
                        $this->data = $count;
		} 
		return;
        }
}