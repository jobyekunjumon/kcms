 <?php

require_once(APPLICATION_PATH . '/../library/mpdf/mpdf.php');

class Application_Model_Utilities {

public function debug($data) {
  echo '<pre>';
    if (is_array($data)) print_r($data);
    else echo $data;
  echo '</pre>';
}

public function getSiteSlug($get) {
  $siteSlug = '';
  if(isset($get['name']) && $get['name']) $siteSlug = $get['name'];
  else {
    $url = $_SERVER['HTTP_HOST'];
    $url = str_replace('http://','',$url);
    $url = str_replace('https://','',$url);
    $url = str_replace('www','',$url);
    $urlTokens = explode('.',$url);
    $siteSlug = $urlTokens[0];
  }

  return $siteSlug;
}

public function getPageSlug($get) {
  $pageSlug = 'home';
  if(isset($get['page']) && $get['page']) $pageSlug = $get['page'];

  return $pageSlug;
}


function getForm($form,$method,$action) {
  $out = '';
  $modelFormElements = new Application_Model_DbTable_Composer();
  $modelFormElements->setTableName('form_elements');
  $modelFormElements->setIdColumn('id_form_element');
  $formElements = $modelFormElements->getAll(' WHERE `id_form` = "'.$form['id_form'].'" AND `element_status` = 1  ORDER BY `id_form_element`');
  if($formElements) {
    $out = '<form role="form" method="'.$method.'" action="'.$action.'" id="'.$form['id_form'].'">';
    $out .= '<input type="hidden" name="id_form" value="'.$form['id_form'].'" />';
    foreach($formElements as $formElement) {
      $elementHtml = '';
      switch ($formElement['element_type']) {
        case 'text':
            $elementHtml .= '<div class="form-group">';
              $elementHtml .= '<label>'.$formElement['element_name'].'</label>';
              $elementHtml .= '<input class="form-control '.$formElement['element_class'].'" type="text" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
              if($formElement['place_holder']) $elementHtml .= 'placeholder="'.$formElement['place_holder'].'"';
              if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
              $elementHtml .= '/>';
            $elementHtml .= '</div>';
          break;
          case 'textarea':
              $elementHtml .= '<div class="form-group">';
                $elementHtml .= '<label>'.$formElement['element_name'].'</label>';
                $elementHtml .= '<textarea class="form-control '.$formElement['element_class'].'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                if($formElement['place_holder']) $elementHtml .= 'place_holder="'.$formElement['place_holder'].'" >';
                if($formElement['default_value']) $elementHtml .= $formElement['default_value'];
                $elementHtml .= '</textarea>';
              $elementHtml .= '</div>';
            break;
            case 'submit':
                $elementClass = 'btn-default';
                if($formElement['element_class']) $elementClass = $formElement['element_class'];
                $elementHtml .= '<div class="form-group">';
                  $elementHtml .= '<input type="submit" class="btn '.$elementClass.'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                  if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
                  $elementHtml .= '/>';
                $elementHtml .= '</div>';
              break;
              case 'button':
                  $elementClass = 'btn-default';
                  if($formElement['element_class']) $elementClass = $formElement['element_class'];
                  $elementHtml .= '<div class="form-group">';
                    $elementHtml .= '<input type="button" class="btn '.$elementClass.'" name="form_element_'.$formElement['id_form_element'].'" id="form_element_'.$formElement['id_form_element'].'" ';
                    if($formElement['default_value']) $elementHtml .= 'value="'.$formElement['default_value'].'"';
                    $elementHtml .= '/>';
                  $elementHtml .= '</div>';
                break;
        default:
          # code...
          break;
      }

      $out .= $elementHtml;
    }
    $out .= '</form>';
  }

  return $out;
}


    public function getMediaUrl($media,$fullyQualified = 0, $thumbnail='') {
  		if(!isset($media) || !$media) return false;

  		$mediaUrl = '';
  		if($fullyQualified) $mediaUrl .= $this->getServerUrl();
  		$mediaUrl .= $media['file_directory'];
  		$mediaUrl .= $media['file_name'];
  		if($thumbnail) $mediaUrl .= '-'.$thumbnail;;
  		$mediaUrl .= '.'.$media['file_extension'];

  		return $mediaUrl;
  	}
	public function composeAndSendMail($emailTo, $templateIdentifier, $variables, $priority=0) {

        if (!trim($templateIdentifier))
            return '';

        $modelMailTemplate = new Application_Model_DbTable_Messagetemplates();
        $mailer = new Application_Model_Mailerqueue();

		$template = $modelMailTemplate->getRowByCondition(' `message_identifier` = "' . $templateIdentifier . '" ');
		if(!$template)
			return '';

		$content = stripslashes($template['template']);

		if (count($variables)) {
			foreach ($variables as $variable => $value) {
				$content = str_replace('$' . $variable, $value, $content);
			}
		}

		$mail[0]['email_to'] = $emailTo;
		$mail[0]['subject'] = $template['title'];
		$mail[0]['message'] = $content;
		$mailer->send($mail);

		return true;

    }

	public function composeAndSendInvoice($emailTo, $templateIdentifier, $variables, $priority=0,$attachment) {

        if (!trim($templateIdentifier))
            return '';

        $modelMailTemplate = new Application_Model_DbTable_Messagetemplates();
        $mailer = new Application_Model_Mailerqueue();

		$template = $modelMailTemplate->getRowByCondition(' `message_identifier` = "' . $templateIdentifier . '" ');
		if(!$template)
			return '';

		$content = stripslashes($template['template']);

		if (count($variables)) {
			foreach ($variables as $variable => $value) {
				$content = str_replace('$' . $variable, $value, $content);
			}
		}

		$mail[0]['email_to'] = $emailTo;
		$mail[0]['subject'] = $template['title'];
		$mail[0]['message'] = $content;
		$mail[0]['attachment'] = $attachment;
		$mailer->send($mail);

		return true;

    }

	public function composeAndSendSms($smsTo, $templateIdentifier, $variables, $title='') {

        if (!trim($templateIdentifier))
            return '';

        $modelSmsTemplate = new Application_Model_DbTable_Smstemplates();
		$modelUsers = new Application_Model_DbTable_Users();

		$content = $modelSmsTemplate->getTemplate($templateIdentifier,'EN');
		if(!$content)
			return '';

		if (count($variables)) {
			foreach ($variables as $variable => $value) {
				$content = str_replace('< ' . $variable .' >', $value, $content);
			}
		}

		$param = array(	'username' => 'dubaitaxicorp',
						'password' => 'dtc901',
						'senderid' => 'SMS Alert',
						'text' => $content,
						'type' => 'text',
						'datetime' => date('Y-m-d H:i:s',(time()-(24*60*60))),
					  );

		//$countryCode = '971';
		//$recipients[] = $countryCode.$smsTo;
		$countryCode1 = '971';
		$countryCode2 = '00971';
		 if (strpos($smsTo, $countryCode1) !== false) {
			$no = explode($countryCode1,$smsTo);
			$recipients[] = $countryCode2.ltrim($no[1], '0');
		} elseif (strpos($smsTo, $countryCode2) !== false) {
			$no = explode($countryCode2,$smsTo);
			$recipients[] = $countryCode2.ltrim($no[1], '0');
		} else {
			$recipients[] = $countryCode2.ltrim($smsTo, '0');
		}

		$post = 'to=' . implode(';', $recipients);

		foreach ($param as $key => $val) {
			$post .= '&' . $key . '=' . rawurlencode($val);
		}
		$smsStatus = 0;

		$url = "http://www.smartsmsgateway.com/api/api_http.php?";
		$url = $url.$post;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		@$result = curl_exec($curl);

		if(curl_errno($curl)) {
			$result = "cURL ERROR: " . curl_errno($curl) . " " . curl_error($curl);
		} else {
			$returnCode = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
			switch($returnCode) {
				case 200 :	$result = $returnCode; $smsStatus = 1; break;
				default  :	$result = "HTTP ERROR: " . $returnCode;
			}
		}
		curl_close($curl);

		// log sms to `send_sms` table
		$modelSentSms = new Application_Model_DbTable_Sentsms();
		$newSms = array();
		$newSms['to'] = $recipients[0];
		$newSms['title'] = $title;
		$newSms['message'] = addslashes($content);
		$newSms['datetime_sent'] = date('Y-m-d H:i:s');
		$newSms['status'] = $smsStatus;
		$newSms['response_status_code'] = $result;
		$newSms['response_data'] = serialize($result);
		$modelSentSms->insertData($newSms);

		return true;
   }

	public function logRequest($idUser, $api, $apiUrl, $scope, $input) {


        $modelApiLog = new Application_Model_DbTable_Apilog();

		$insertInputs = array('id_user' =>(isset($idUser) && $idUser)?$idUser:0,
							  'api' => $api,
							  'api_url' => $apiUrl,
							  'scope' => $scope,
							  'inputs' => $input,
							  'call_on' => date('Y-m-d H:i:s'),
							  );
		if($id_log = $modelApiLog->insertData($insertInputs)){
			return $id_log;
		} else {
			return false;
		}

	}

	public function updateApiLog($idLog, $response='', $responseCode='',$idUser='') {


        $modelApiLog = new Application_Model_DbTable_Apilog();

		$updateLog = array('response' =>$response,
						   'response_code' => $responseCode
						   );
		if($idUser) $updateLog['id_user'] = $idUser;
		if($modelApiLog->updateData($updateLog,$idLog)){
			return true;
		} else {
			return false;
		}

	}

	public function intaxiLogRequest($idUser, $api, $apiUrl, $scope, $input) {


        $modelApiLog = new Application_Model_DbTable_Intaxiapilog();

		$insertInputs = array('id_user' =>(isset($idUser) && $idUser)?$idUser:0,
							  'api' => $api,
							  'api_url' => $apiUrl,
							  'inputs' => $input,
							  'call_on' => date('Y-m-d H:i:s'),
							  );
		if($id_log = $modelApiLog->insertData($insertInputs)){
			return $id_log;
		} else {
			return false;
		}

	}

	public function updateIntaxiApiLog($idLog, $response='',$idUser='') {


        $modelApiLog = new Application_Model_DbTable_Intaxiapilog();

		$updateLog = array('response' =>$response  );
		if($idUser) $updateLog['id_user'] = $idUser;
		if($modelApiLog->updateData($updateLog,$idLog)){
			return true;
		} else {
			return false;
		}

	}

	public function callLoadTester($params){
		$get = '';
		foreach ($params as $key => $val) {
			$get .= '&' . $key . '=' . $val;
		}
		$request = 'http://ntglobal.org/works/load-test/?'.$get;
		$file_contents = file_get_contents($request);

		return true;

	}

	public function getDriverRating($idDriver) {

		if(!$idDriver) return 0;

        $modelDriverRating = new Application_Model_DbTable_Driverrating();

		$rating = $modelDriverRating->getAll('','SELECT AVG(`rating`) AS `rating` , COUNT(*) as count FROM `driver_rating` WHERE `id_driver` = '.$idDriver);
		if(isset($rating[0]) && $rating[0] ) return $rating[0];
		else return 0;


	}
	public function getBoundaryCoordinates( $lat, $lng, $distance = 100, $unit = 'km' ) {
		// radius of earth; @note: the earth is not perfectly spherical, but this is considered the 'mean radius'
		if( $unit == 'km' ) { $radius = 6371.009; }
		elseif ( $unit == 'mi' ) { $radius = 3958.761; }

		// latitude boundaries
		$maxLat = ( float ) $lat + rad2deg( $distance / $radius );
		$minLat = ( float ) $lat - rad2deg( $distance / $radius );

		// longitude boundaries (longitude gets smaller when latitude increases)
		$maxLng = ( float ) $lng + rad2deg( $distance / $radius) / cos( deg2rad( ( float ) $lat ) );
		$minLng = ( float ) $lng - rad2deg( $distance / $radius) / cos( deg2rad( ( float ) $lat ) );

		$max_min_values = array(
			'max_latitude' => $maxLat,
			'min_latitude' => $minLat,
			'max_longitude' => $maxLng,
			'min_longitude' => $minLng
		);

		return $max_min_values;
	}
	public function getNearbyVehicles( $lat, $lng, $distance = 100, $unit = 'km' ) {
		// radius of earth; @note: the earth is not perfectly spherical, but this is considered the 'mean radius'
		if( $unit == 'km' ) { $radius = 6371.009; }
		elseif ( $unit == 'mi' ) { $radius = 3958.761; }

		// latitude boundaries
		$maxLat = ( float ) $lat + rad2deg( $distance / $radius );
		$minLat = ( float ) $lat - rad2deg( $distance / $radius );

		// longitude boundaries (longitude gets smaller when latitude increases)
		$maxLng = ( float ) $lng + rad2deg( $distance / $radius) / cos( deg2rad( ( float ) $lat ) );
		$minLng = ( float ) $lng - rad2deg( $distance / $radius) / cos( deg2rad( ( float ) $lat ) );

		$max_min_values = array(
			'max_latitude' => $maxLat,
			'min_latitude' => $minLat,
			'max_longitude' => $maxLng,
			'min_longitude' => $minLng
		);

		return $max_min_values;
	}

	/**
	 * Calculates the great-circle distance between two points, with
	 * the Vincenty formula.
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 */
	public static function getDistanceBetweenTwoLatLng($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371.009){
	  // convert from degrees to radians
	  $latFrom = deg2rad($latitudeFrom);
	  $lonFrom = deg2rad($longitudeFrom);
	  $latTo = deg2rad($latitudeTo);
	  $lonTo = deg2rad($longitudeTo);

	  $lonDelta = $lonTo - $lonFrom;
	  $a = pow(cos($latTo) * sin($lonDelta), 2) +
		pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
	  $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

	  $angle = atan2(sqrt($a), $b);
	  return $angle * $earthRadius;
	}

	public static function getTimeBetweenTwoLatLng($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo){

		$url ="https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$latitudeFrom.",".$longitudeFrom."&destinations=".$latitudeTo.",".$longitudeTo;
		$ch = curl_init();
		// Disable SSL verification

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// Will return the response, if false it print the response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Set the url
		curl_setopt($ch, CURLOPT_URL,$url);
		// Execute
		$result=curl_exec($ch);
		// Closing
		curl_close($ch);

		$result_array=json_decode($result);

		return $result_array->rows[0]->elements[0]->duration->text;

	}

	public function callIntaxiApi($params,$userId, $title = ''){

		$configs = new Application_Model_DbTable_Configurations();
		$get = '';
		$conj = '?';
		foreach ($params as $key => $val) {
			$get .= $conj . $key . '=' . rawurlencode($val);
			$conj = '&';
		}

		$intaxiUrl = $configs->getConfig('INTAXI_URL');
		$url = $intaxiUrl.$get;
		$idIntaxiLogEntry =  $this->intaxiLogRequest($userId,$title,$url,'intaxi',serialize($params));

		$login = 'smarttown';
		$password = 'fB2tuj4h';//'st789'; //'live - fB2tuj4h'; // test credential 'st789';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, "$login:$password");
		$result = curl_exec($ch);
		$result_array=json_decode($result);

		$this->updateIntaxiApiLog($idIntaxiLogEntry,serialize($result_array));

		return $result_array;

	}

	public static function getLocationByLatLng($latitude, $longitude){

		if(!empty($latitude) && !empty($longitude)){

			$geolocation = $latitude.','.$longitude;

			$request = 'http://maps.googleapis.com/maps/api/geocode/json?latlng='.$geolocation.'&sensor=false';

			$file_contents = file_get_contents($request);

			$json_decode = json_decode($file_contents);

			if(isset($json_decode->results[0])) {
				return $json_decode->results[0]->formatted_address;
			} else return '';
		}else{
			return '';
		}

	}
	public function getInvoiceHtmlTest($requestId){

		$configs = new Application_Model_DbTable_Configurations();
		$baseUrl = $configs->getConfig('BASE_URL');
		//echo '1';
		$modelRequests = new Application_Model_DbTable_Requests();
		$onGoingRequest = $modelRequests->getRowById($requestId);
		//echo '2';
		$modelInvoices = new Application_Model_DbTable_Invoices();

		$invoice = $modelInvoices->getRowByCondition(' `id_request` = '.$requestId);
		if(!$invoice) return '';
		//echo '3';
		$configs = new Application_Model_DbTable_Configurations();
		$currency = $configs->getConfig('CURRENCY');
		//echo '4';
		$amountBreakdown = unserialize($invoice['amount_breakdown']);

		$modelServices = new Application_Model_DbTable_Services();
		if(isset($onGoingRequest['requested_services']) && $onGoingRequest['requested_services'] ){
			$services = explode(',',$onGoingRequest['requested_services']);
			foreach($services as $service){
				$ser = $modelServices->getRowById($service);
				if($ser){
					$onGoingRequest['services'][] = $ser['service_name'];
				}
			}
		}

		//echo '5';
		$modelDriver = new Application_Model_DbTable_Drivers();
		if(isset($onGoingRequest['accepted_driver']) && $onGoingRequest['accepted_driver'] ){
			$driver = $modelDriver->getRowById($onGoingRequest['accepted_driver']);
			//echo '6';
		}
		$modelRecoverVehicles = new Application_Model_DbTable_Recoveryvehicles();
		if(isset($onGoingRequest['accepted_recovery_vehicle']) && $onGoingRequest['accepted_recovery_vehicle'] ){
			$vehicle = $modelRecoverVehicles->getRowById($onGoingRequest['accepted_recovery_vehicle']);
			//echo '7';
		}

		$modelDriverRating = new Application_Model_DbTable_Driverrating();
		$rating = $modelDriverRating->getAll('','SELECT AVG(`rating`) AS `avg_rating` FROM `driver_rating` WHERE `id_driver` = '.$onGoingRequest['accepted_driver']);
		//echo '8';

		$out = '';

		$out .= '<html>
				   <body style="margin: 0;">
					  <ul style="list-style-type: none; margin: 0;padding: 0;overflow: hidden;background-color: #000;margin-left: -8px;margin-right: -9px;margin-bottom: 11px;margin-top: -8px;height:65px">
						 <li style="float:left"><a style="  display: inline-block;color: white; text-align: center; padding: 14px 16px; text-decoration: none;  " href="#home"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;">'.date('d M Y').'</span></a></li>
						 <li style="float:right"><a style="  display: inline-block;color: white; text-align: center; padding: 14px 16px; text-decoration: none;  " href="#home"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;">Smart Tow</span></a></li>
					  </ul>
					  <div class="aed" style="width:100%;height: 143px;">
						 <!-- Main Div -->
						 <div class="towing" style="float:right;margin-bottom: 0px;margin-top: 24px;margin-left: 0px;margin-right: 76px;">
							<span class="lfont" style="font-family: Calibri;font-size:25px;">'.implode(',',$onGoingRequest['services']).'</span>
						 </div>
						 <div style="float:left; width:80%; margin-left:10px;">
							<span class="lfont" style="font-family: Calibri;font-size:55px;">'.$currency.' '.number_format((float)$invoice['amount_paid'], 2, '.', '').'</span>  <!-- Set Div As your requirement -->
						 </div>
					  </div>
					  <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					  <div class="tablesection" style="height: 134px;">
						 <h1>  </h1>
						 <table style="border-collapse:separate;border-spacing:81px 8px;">
							<tr>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							   if($onGoingRequest['requested_services'] == 1)
								   		$out .= date('h:i A', strtotime($onGoingRequest['service_started_time']));
									else
										$out .= date('h:i A', strtotime($onGoingRequest['driver_start_time']));
							   $out .= '</span> </td>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
									else
										$out .= $this->getLocationByLatLng($onGoingRequest['driver_starting_lat'],$onGoingRequest['driver_starting_lat']);
							   $out .= '</span></td>
							</tr>
							<tr class="separator" style="width:10px;">
							<tr>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
									else
										$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
							   $out .= '</span> </td>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= $this->getLocationByLatLng($onGoingRequest['towing_destination_lat'],$onGoingRequest['towing_destination_long']);
									else
										$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
							   $out .= '</span> </td>
							</tr>
						 </table>
						 <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					  </div>
					  <div style="width:100%;height:77px;clear:both;">
						 <!-- Main Div -->
						 <span class="bold"  style="font-family: Calibri;font-size: 26px;margin-top: 20px">'.$vehicle['plate_code'].$vehicle['reg_number'].'</span>
						 <div style="float:left; width:20%;">
							<!-- Set Div As your requirement -->
						 </div>
						 <div style="float:left; width:80%; margin-left:10px;">
							<img src="'.$driver['profile_image'].'" style="height: 63px;width: 64px;border-radius: 35px;margin-top: 20px"></img><span class="bold" style="font-family: Calibri;font-size: 26px;"> '.$driver['first_name'].' '.$driver['last_name'].' '.ceil($rating[0]['avg_rating']).'</span> <img src="'.$baseUrl.'/ui_backoffice/images/star.png" style="margin-left: 20px;margin-bottom: 10px;"></span>  <!-- Set Div As your requirement -->
						 </div>
					  </div>
					 <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					   <div class="newtable">
					  <table style="border-collapse:separate;border-spacing:100px 8px;width: 100%;">
							';
							foreach($amountBreakdown['split_up'] as $service){
								$out .='<tr>';
									$out .= '<td><span class="font" style="font-family: Calibri;font-size: 17px;">'.$service['title'].'</span> </td>';
									$out .= '<td><span class="font" style="font-family: Calibri;font-size: 17px;">'.$currency.' '.number_format((float)$service['total'], 2, '.', '').'</span> </td>';
								$out .='</tr>';
							  }

					 $out .= ' </table>
					  <div class="shade" style="background:#e9e9e9;height:100px;">
					  <table style="border-collapse:separate;border-spacing:100px 8px;width: 100%;">
					  <tr>
					  <td><span class="fontbold" style="font-family: Calibri;font-size: 22px;">TOTAL </span></td>
					  <td> <span class="fontbold" style="font-family: Calibri;font-size: 22px;"> '.$currency.' '.number_format((float)$amountBreakdown['grand_total'], 2, '.', '').'</span></td>
					  </tr>
					  </table>
					  </div>
					  </div>
					  <div id="footer"   style="position: relative;background: #000;height: 60px; width: 100%;bottom: 0px;">
						 <img style="margin-top: 15px;margin-left: 20px" class="vector.png" src="'.$baseUrl.'/ui_backoffice/images/vector.png"> <span class="white" style="font-family: Calibri;color:#fff;font-size: 22px;" >'.ucwords($onGoingRequest['payment_method']).'</span></img>
					  </div>
				   </body>
				</html>';
		return $out;
	}

	public function getInvoiceHtmlOld($requestId){

		$configs = new Application_Model_DbTable_Configurations();
		$baseUrl = $configs->getConfig('BASE_URL');
		//echo '1';
		$modelRequests = new Application_Model_DbTable_Requests();
		$onGoingRequest = $modelRequests->getRowById($requestId);
		//echo '2';
		$modelInvoices = new Application_Model_DbTable_Invoices();

		$invoice = $modelInvoices->getRowByCondition(' `id_request` = '.$requestId);
		if(!$invoice) return '';
		//echo '3';
		$configs = new Application_Model_DbTable_Configurations();
		$currency = $configs->getConfig('CURRENCY');
		//echo '4';
		$amountBreakdown = unserialize($invoice['amount_breakdown']);

		$modelServices = new Application_Model_DbTable_Services();
		if(isset($onGoingRequest['requested_services']) && $onGoingRequest['requested_services'] ){
			$services = explode(',',$onGoingRequest['requested_services']);
			foreach($services as $service){
				$ser = $modelServices->getRowById($service);
				if($ser){
					$onGoingRequest['services'][] = $ser['service_name'];
				}
			}
		}

		//echo '5';
		$modelDriver = new Application_Model_DbTable_Drivers();
		if(isset($onGoingRequest['accepted_driver']) && $onGoingRequest['accepted_driver'] ){
			$driver = $modelDriver->getRowById($onGoingRequest['accepted_driver']);
			//echo '6';
		}
		$modelRecoverVehicles = new Application_Model_DbTable_Recoveryvehicles();
		if(isset($onGoingRequest['accepted_recovery_vehicle']) && $onGoingRequest['accepted_recovery_vehicle'] ){
			$vehicle = $modelRecoverVehicles->getRowById($onGoingRequest['accepted_recovery_vehicle']);
			//echo '7';
		}

		$modelDriverRating = new Application_Model_DbTable_Driverrating();
		$rating = $modelDriverRating->getAll('','SELECT AVG(`rating`) AS `avg_rating` FROM `driver_rating` WHERE `id_driver` = '.$onGoingRequest['accepted_driver']);
		//echo '8';

		$out = '';

		$out .= '<html>
				   <body style="margin: 0;">
					  <ul style="list-style-type: none; margin: 0;padding: 0;overflow: hidden;background-color: #000;margin-left: -8px;margin-right: -9px;margin-bottom: 11px;margin-top: -8px;height:65px">
						 <li style="float:left"><a style="  display: inline-block;color: white; text-align: center; padding: 14px 16px; text-decoration: none;  " href="#home"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;">'.date('d M Y').'</span></a></li>
						 <li style="float:right"><a style="  display: inline-block;color: white; text-align: center; padding: 14px 16px; text-decoration: none;  " href="#home"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;">Smart Tow</span></a></li>
					  </ul>
					  <div class="aed" style="width:100%;height: 143px;">
						 <!-- Main Div -->
						 <div class="towing" style="float:right;margin-bottom: 0px;margin-top: 24px;margin-left: 0px;margin-right: 76px;">
							<span class="lfont" style="font-family: Calibri;font-size:25px;">'.implode(',',$onGoingRequest['services']).'</span>
						 </div>
						 <div style="float:left; width:80%; margin-left:10px;">
							<span class="lfont" style="font-family: Calibri;font-size:55px;">'.$currency.' '.number_format((float)$invoice['amount_paid'], 2, '.', '').'</span>  <!-- Set Div As your requirement -->
						 </div>
					  </div>
					  <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					  <div class="tablesection" style="height: 134px;">
						 <h1>  </h1>
						 <table style="border-collapse:separate;border-spacing:81px 8px;">
							<tr>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							   if($onGoingRequest['requested_services'] == 1)
								   		$out .= date('h:i A', strtotime($onGoingRequest['service_started_time']));
									else
										$out .= date('h:i A', strtotime($onGoingRequest['driver_start_time']));
							   $out .= '</span> </td>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
									else
										$out .= $this->getLocationByLatLng($onGoingRequest['driver_starting_lat'],$onGoingRequest['driver_starting_lat']);
							   $out .= '</span></td>
							</tr>
							<tr class="separator" style="width:10px;">
							<tr>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
									else
										$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
							   $out .= '</span> </td>
							   <td><span class="font" style="font-family: Calibri;font-size:17px;">';
							    if($onGoingRequest['requested_services'] == 1)
								   		$out .= $this->getLocationByLatLng($onGoingRequest['towing_destination_lat'],$onGoingRequest['towing_destination_long']);
									else
										$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
							   $out .= '</span> </td>
							</tr>
						 </table>
						 <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					  </div>
					  <div style="width:100%;height:77px;clear:both;">
						 <!-- Main Div -->
						 <span class="bold"  style="font-family: Calibri;font-size: 26px;margin-top: 20px">'.$vehicle['plate_code'].$vehicle['reg_number'].'</span>
						 <div style="float:left; width:20%;">
							<!-- Set Div As your requirement -->
						 </div>
						 <div style="float:left; width:80%; margin-left:10px;">
							<img src="'.$driver['profile_image'].'" style="height: 63px;width: 64px;border-radius: 35px;margin-top: 20px"></img><span class="bold" style="font-family: Calibri;font-size: 26px;"> '.$driver['first_name'].' '.$driver['last_name'].' '.ceil($rating[0]['avg_rating']).'</span> <img src="'.$baseUrl.'/ui_backoffice/images/star.png" style="margin-left: 20px;margin-bottom: 10px;"></span>  <!-- Set Div As your requirement -->
						 </div>
					  </div>
					 <hr style="margin-top: 20px;margin-bottom: 20px;border: 0;border-top: 1px solid #eee;">
					   <div class="newtable">
					  <table style="border-collapse:separate;border-spacing:100px 8px;width: 100%;">
							';
							foreach($amountBreakdown['split_up'] as $service){
								$out .='<tr>';
									$out .= '<td><span class="font" style="font-family: Calibri;font-size: 17px;">'.$service['title'].'</span> </td>';
									$out .= '<td><span class="font" style="font-family: Calibri;font-size: 17px;">'.$currency.' '.number_format((float)$service['total'], 2, '.', '').'</span> </td>';
								$out .='</tr>';
							  }

					 $out .= ' </table>
					  <div class="shade" style="background:#e9e9e9;height:100px;">
					  <table style="border-collapse:separate;border-spacing:100px 8px;width: 100%;">
					  <tr>
					  <td><span class="fontbold" style="font-family: Calibri;font-size: 22px;">TOTAL </span></td>
					  <td> <span class="fontbold" style="font-family: Calibri;font-size: 22px;"> '.$currency.' '.number_format((float)$amountBreakdown['grand_total'], 2, '.', '').'</span></td>
					  </tr>
					  </table>
					  </div>
					  </div>
					  <div id="footer"   style="position: relative;background: #000;height: 60px; width: 100%;bottom: 0px;">
						 <img style="margin-top: 15px;margin-left: 20px" class="vector.png" src="'.$baseUrl.'/ui_backoffice/images/vector.png"> <span class="white" style="font-family: Calibri;color:#fff;font-size: 22px;" >'.ucwords($onGoingRequest['payment_method']).'</span></img>
					  </div>
				   </body>
				</html>';
		return $out;
	}

	public function getInvoiceHtml($requestId){

		$configs = new Application_Model_DbTable_Configurations();
		$baseUrl = $configs->getConfig('BASE_URL');
		//echo '1';
		$modelRequests = new Application_Model_DbTable_Requests();
		$onGoingRequest = $modelRequests->getRowById($requestId);
		//echo '2';
		$modelInvoices = new Application_Model_DbTable_Invoices();

		$invoice = $modelInvoices->getRowByCondition(' `id_request` = '.$requestId);
		if(!$invoice) return '';
		//echo '3';
		$configs = new Application_Model_DbTable_Configurations();
		$currency = $configs->getConfig('CURRENCY');
		//echo '4';
		$amountBreakdown = unserialize($invoice['amount_breakdown']);

		$modelServices = new Application_Model_DbTable_Services();
		if(isset($onGoingRequest['requested_services']) && $onGoingRequest['requested_services'] ){
			$services = explode(',',$onGoingRequest['requested_services']);
			foreach($services as $service){
				$ser = $modelServices->getRowById($service);
				if($ser){
					$onGoingRequest['services'][] = $ser['service_name'];
				}
			}
		}

		//echo '5';
		$modelDriver = new Application_Model_DbTable_Drivers();
		if(isset($onGoingRequest['accepted_driver']) && $onGoingRequest['accepted_driver'] ){
			$driver = $modelDriver->getRowById($onGoingRequest['accepted_driver']);
			//echo '6';
		}
		$modelRecoverVehicles = new Application_Model_DbTable_Recoveryvehicles();
		if(isset($onGoingRequest['accepted_recovery_vehicle']) && $onGoingRequest['accepted_recovery_vehicle'] ){
			$vehicle = $modelRecoverVehicles->getRowById($onGoingRequest['accepted_recovery_vehicle']);
			//echo '7';
		}

		$modelDriverRating = new Application_Model_DbTable_Driverrating();
		$rating = $modelDriverRating->getAll('','SELECT AVG(`rating`) AS `avg_rating` FROM `driver_rating` WHERE `id_driver` = '.$onGoingRequest['accepted_driver']);
		//echo '8';

		$out = '';

		$out .= '<!DOCTYPE html>
					<html lang="en">
					   <meta charset="utf-8">
					   <meta http-equiv="X-UA-Compatible" content="IE=edge">
					   <meta name="viewport" content="width=device-width, initial-scale=1">

						  <head>
						  </head>
						  <body style="margin: 0px; padding: 0px; font-family: \'Trebuchet MS\',verdana;">
							 <table width="100%" style="height: ;" cellpadding="10" cellspacing="0" border="0">
							 	<tr>
								   <td colspan="3"  align="left" height="30"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;"><img class="vector.png" src="'.$baseUrl.'/ui_backoffice/images/rta-logo.png"> </img></span>
									 </td>
									 <td colspan="2"  align="left" height="30"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 23px;"><img style="height: 80px;width: 200px;" class="vector.png" src="'.$baseUrl.'/ui_backoffice/images/dtc_logo_Dark.png"> </img></span>

								   </td>

								</tr>
								<tr bgcolor="#000000">
								   <td colspan="3"  align="left" height="30"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 25px;">'.date("d M Y").'</span>
							 </td>
							 <td colspan="2"  align="left" height="30"><span class="heading" style="font-family: Calibri;color: #fff;font-size: 23px;">SmartTow</span>

						   </td>

						</tr>
						<tr style="height: 200px;">
						   <td colspan="2"></td>
						   <td style="text-align:right"><span class="font" style="font-family: Calibri;font-size: 25px;">Invoice Number : </span> </td>
						   <td><span class="lfont" style="font-family: Calibri;font-size: 25px;">'.$invoice['id_invoice'].'</span></td>
						</tr>

						<tr style="height: 200px;">
						   <td colspan="2">
							  <div style="float:left;"><span class="lfont" style="font-family: Calibri;font-size:25px;">'.$currency.' '.number_format((float)$invoice['amount_paid'], 2, '.', '').'</span></div>
						   </td>

						   <td colspan="3">
							  <div style="float:right;"><span class="lfont" style="font-family: Calibri;font-size:30px;">'.implode(',',$onGoingRequest['services']).' </span></div>
						   </td>
						</tr>
						<tr style="height: 100px;">
							<td style="float:right;">';
								if($onGoingRequest['requested_services'] == 1)
								   	$out .= date('h:i A', strtotime($onGoingRequest['service_started_time']));
								else
									$out .= date('h:i A', strtotime($onGoingRequest['driver_start_time']));
							   $out .= '</td>
							    <td colspan="2" style="float:left;">';
								if($onGoingRequest['requested_services'] == 1)
									$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
								else
									$out .= $this->getLocationByLatLng($onGoingRequest['driver_starting_lat'],$onGoingRequest['driver_starting_long']);
							   $out .= '</td>
						</tr>

						<tr>
								<td style="float:right;">';
								if($onGoingRequest['requested_services'] == 1)
								   		$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
									else
										$out .= date('h:i A', strtotime($onGoingRequest['service_completed_time']));
							   $out .= '</td>
							    <td colspan="2" style="float:left;">';
								if($onGoingRequest['requested_services'] == 1)
								   		$out .= $this->getLocationByLatLng($onGoingRequest['towing_destination_lat'],$onGoingRequest['towing_destination_long']);
									else
										$out .= $this->getLocationByLatLng($onGoingRequest['user_lat'],$onGoingRequest['user_long']);
							   $out .= '</td>
						</tr>

						<tr style="height:200px;">
							<td><img src="'.$driver['profile_image'].'" class="john" alt="" style="width:50px; height:auto;margin-bottom: -7px;border-radius:30px;"></img> </td>
							<td colspan="2" ><span class="bold" style="font-family: Calibri;font-size: 26px;margin-left: 13px;">'.$driver['first_name'].' '.$driver['last_name'].' '.ceil($rating[0]['avg_rating']).' </span>  <img src="'.$baseUrl.'/ui_backoffice/images/star.png"></img></td>
							<td><span class="bold" style="font-family: Calibri;font-size: 26px;">'.$vehicle['plate_code'].$vehicle['reg_number'].'</span></td>
						</tr>';
						foreach($amountBreakdown['split_up'] as $service){
						$out .= '<tr style="height:200px;">';
							$out .= '<td colspan="3"><span class="font" style="font-family: Calibri;font-size: 17px;">'.$service['title'].'</span></td>';
							$out .= '<td colspan="2"><span class="font" style="font-family: Calibri;font-size: 17px;">'.$currency.' '.number_format((float)$service['total'], 2, '.', '').'</span></td>';
						$out .= '</tr>';
						}

						$out .=' <tr bgcolor="#e9e9e9" style="height:200px;">

								   <td colspan="3"  align="right" height="30"><span class="heading" style="font-family: Calibri;color: #000;font-size: 25px;">TOTAL</span>

								   </td>
									  <td colspan="2"  align="left" height="30"><span class="heading" style="font-family: Calibri;color: #000;font-size: 25px;">'.$currency.' '.number_format((float)$amountBreakdown['grand_total'], 2, '.', '').'</span>

								   </td>

								</tr>

						<tr>
						   <td colspan="5" style="border-top-width:0px;border-top-style: solid;border-color: #fff;" bgcolor="#000000" align="left" height="20">       <img class="vector.png" src="'.$baseUrl.'/ui_backoffice/images/vector.png"> </img><span class="heading" style="font-family: Calibri;color: #fff;font-size: 20px;">'.ucwords($onGoingRequest['payment_method']).' Payment'.'</span></td>
						</tr>

					 </table>
					 <p>*This is a system generated invoice</p>
				  </body>
			   </html>';
		return $out;
	}


    public function composeMessageHtml($message, $type) {
        $labelClass = array('error' => 'alert-danger', 'warning' => 'alert-warning', 'info' => 'alert-info', 'success' => 'alert-success');
        $icons = array('error' => 'fa-ban', 'warning' => 'fa-warning', 'info' => 'fa-info', 'success' => 'fa-check');

        $out = '<div class="alert ' . $labelClass[$type] . ' alert-dismissable">
					<i class="fa ' . $icons[$type] . '"></i>
					<a class="close" aria-hidden="true" href="#" data-dismiss="alert">×</a>
					' . $message . '
				</div>';

        return $out;
    }

    public function getUrlParams($data, $excludedParams = array()) {
        $conjuction = '';
        $out = '';
        if ($data && count($data) > 0) {
            foreach ($data as $key => $value) {

                if (($value != '') && !in_array($key, $excludedParams)) {
                    if (is_array($value)) {
                        foreach ($value as $subKey => $subVal ) {
							if (is_array($subVal)) {
								foreach ($subVal as $keyy => $val) {
									$out .= $conjuction . $key . '%5B0%5D%5B';
									$out .= $keyy . '%5D=' . $val;
									$conjuction = '&';
								}
							}
							else {
								$out .= $conjuction . $key . '%5B%5D=' . $subVal;
								$conjuction = '&';
							}
                        }
                    } else {
                        $out .= $conjuction . $key . '=' . $value;
                        $conjuction = '&';
                    }
                }
            }
        }
        return $out;
    }


    public function flipKeys($res, $id) {
        if (!is_array($res) || !count($res))
            return '';
        $out = array();
        foreach ($res as $entry) {
            $out[$entry[$id]] = $entry;
        }
        return $out;
    }

    public function paginate_two($reload, $page, $tpages, $adjacents = 4,$paginationVariable = '') {
		$pVar = 'page';
		if($paginationVariable) $pVar = $paginationVariable;
        $firstlabel = "&laquo;&nbsp;";
        $prevlabel = "&lsaquo;&nbsp;";
        $nextlabel = "&nbsp;&rsaquo;";
        $lastlabel = "&nbsp;&raquo;";

        $out = "<ul class='pagination'>";

        // first
        if ($page > ($adjacents + 1)) {
            $out.= "<li><a href=\"" . $reload . "\">" . $firstlabel . "</a></li>";
        } else {
            $out.= "<li><span>" . $firstlabel . "</span></li>";
        }

        // previous
        if ($page == 1) {
            $out.= "<li><span>" . $prevlabel . "</span></li>";
        } elseif ($page == 2) {
            $out.= "<li><a href=\"" . $reload . "\">" . $prevlabel . "</a></li>";
        } else {
            $out.= "<li><a href=\"" . $reload . "&amp;".$pVar."=" . ($page - 1) . "\">" . $prevlabel . "</a></li>";
        }

        // 1 2 3 4 etc
        $pmin = ($page > $adjacents) ? ($page - $adjacents) : 1;
        $pmax = ($page < ($tpages - $adjacents)) ? ($page + $adjacents) : $tpages;
        for ($i = $pmin; $i <= $pmax; $i++) {
            if ($i == $page) {
                $out.= "<li><span class=\"current\">" . $i . "</span></li>";
            } elseif ($i == 1) {
                $out.= "<li><a href=\"" . $reload . "\">" . $i . "</a></li>";
            } else {
                $out.= "<li><a href=\"" . $reload . "&amp;".$pVar."=" . $i . "\">" . $i . "</a></li>";
            }
        }

        // next
        if ($page < $tpages) {
            $out.= "<li><a href=\"" . $reload . "&amp;".$pVar."=" . ($page + 1) . "\">" . $nextlabel . "</a></li>";
        } else {
            $out.= "<li><span>" . $nextlabel . "</span></li>";
        }

        // last
        if ($page < ($tpages - $adjacents)) {
            $out.= "<li><a href=\"" . $reload . "&amp;".$pVar."=" . $tpages . "\">" . $lastlabel . "</a></li>";
        } else {
            $out.= "<li><span>" . $lastlabel . "</span></li>";
        }

        $out.= "</ul>";

        return $out;
    }

	function convertUTCtoQAT($date) {
        if(!$date) return '';
        return date('Y-m-d H:i:s',strtotime($date)+(3*60*60));
    }

    function crypto_rand_secure($min, $max) {
        $range = $max - $min;
        if ($range < 0)
            return $min; // not so random...
        $log = log($range, 2);
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd >= $range);
        return $min + $rnd;
    }

	function getSalt($length = 32) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet.= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $token;
    }

    function getPromocodeSalt($length = 32) {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $token;
    }

    function getOTP($length = 32) {
        $token = "";
        $codeAlphabet = "0123456789";
        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, strlen($codeAlphabet))];
        }
        return $token;
    }

    public function downloadpdf($out, $filename) {
        // generate pdf

        $mpdf = new mPDF('c', 'A4');
        $mpdf->WriteHTML($out);
        $mpdf->Output($filename, 'D');
        return true;
    }



    public function array_to_csv_download($array, $filename = "export.csv", $delimiter = ";") {
        // open raw memory as file so no temp files needed, you might run out of memory though
        $f = fopen('php://memory', 'w');
        // loop over the input array
        foreach ($array as $line) {
            // generate csv lines from the inner arrays
            fputcsv($f, $line, $delimiter);
        }
        // rewrind the "file" with the csv lines
        fseek($f, 0);
        // tell the browser it's going to be a csv file
        header('Content-Type: application/csv');
        // tell the browser we want to save it instead of displaying it
        header('Content-Disposition: attachement; filename="' . $filename . '"');
        // make php send the generated csv lines to the browser
        fpassthru($f);
        return true;
    }

    public function cleanData(&$str) {
        $str = preg_replace("/\t/", "\\t", $str);
        $str = preg_replace("/\r?\n/", "\\n", $str);
        if (strstr($str, '"'))
            $str = '"' . str_replace('"', '""', $str) . '"';
    }

    public function getServerUrl() {
        $url = '';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) {
            $url = 'https://' . $_SERVER["SERVER_NAME"];
        } else {
            $url = 'http://' . $_SERVER["SERVER_NAME"];
        }

        return $url;
    }

    public function generateExcel($data, $tableHeaders, $filename = "export.xls") {
        $out = '';
        if (!$data)
            return false;
        $out = array();

        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Type: application/vnd.ms-excel");
		$head = array();
		if($tableHeaders){
			foreach($tableHeaders as $header){
				$head[] = ucwords(str_replace('_', ' ', $header));
			}
		}
        echo implode("\t", array_values($head)) . "\n";

        foreach ($data as $row) {
			$csvRow = array();
			 foreach ($tableHeaders as $field) {
                 $csvRow[] =  $row[$field];
            }
			array_walk($csvRow, array(&$this, 'cleanData'));
            echo implode("\t", array_values($csvRow)) . "\n";
        }
        return true;
    }

    public function generateCSV($data, $tableHeaders, $filename = "export.csv") {

        if (!$data)
            return false;
        $out = array();

        $out[] = $tableHeaders;
        foreach ($data as $row) {
            $row = array_values($row);
            $csvRow = array();
            for ($fieldCnt = 0; $fieldCnt < (count($tableHeaders) ); $fieldCnt++) {
                $csvRow[$fieldCnt] = $row[$fieldCnt];
            }
            $out[] = $csvRow;
        }

        $this->array_to_csv_download($out, $filename);
        return true;
    }

	 public function generateCSVReport($data, $fields, $filename = "export.csv", $includeHeader = 1,$incomeReport = 0) {
        if (!$data)
            return false;

        $out = array();

        if ($includeHeader) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $header) {
                $csvRow[$fieldCnt++] = ucwords(str_replace('_', ' ', $header));
            }
            $out[] = $csvRow;
        }

        foreach ($data as $row) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $field) {
                $csvRow[$fieldCnt++] = $row[$field];
            }
            $out[] = $csvRow;
        }

        $this->array_to_csv_download($out, $filename);

        return true;
    }

	public function generateCSVReportDetail($data, $fields, $filename = "export.csv", $includeHeader = 1,$incomeReport = 0) {
        if (!$data)
            return false;

        $out = array();

        if ($includeHeader) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $header) {
                $csvRow[$fieldCnt++] = ucwords(str_replace('_', ' ', $header));
            }
            $out[] = $csvRow;
        }

        foreach ($data as $row) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $field) {
                $csvRow[$fieldCnt++] = $row[$field];
				if($field == 'credit_amount')	$csvRow[$fieldCnt-1] = number_format((float)$row[$field], 2, '.', '');
				if($field == 'debit_amount')	$csvRow[$fieldCnt-1] = number_format((float)$row[$field], 2, '.', '');
				if($field == 'normal_recovery_amount')	$csvRow[$fieldCnt-1] = number_format((float)$row[$field], 2, '.', '');
				if($field == 'remaining_recovery_amount')	$csvRow[$fieldCnt-1] = number_format((float)$row[$field], 2, '.', '');

				if(($field == 'activation_status') && ($row['activation_status'] == 0)){
					$csvRow[$fieldCnt-1] =	'Failure';
				}
				else if(($field == 'activation_status') && ($row['activation_status'] == 1)){
					$csvRow[$fieldCnt-1] =	'Success';
				}
				$pendingAmt = $row['normal_recovery_amount']-$row['remaining_recovery_amount'];
				if($field == 'Pending Amt.'){
					$csvRow[$fieldCnt-1] =	number_format((float)$pendingAmt, 2, '.', '');
				}
            }
            $out[] = $csvRow;
        }

       /* echo '<pre>';
        if (is_array($out))
            print_r($out);
        else
            echo $out;
        echo '</pre>';*/

        $this->array_to_csv_download($out, $filename);

        return true;
    }

	public function generateCSVReportTransaction($data, $fields, $filename = "export.csv", $includeHeader = 1,$incomeReport = 0) {
        if (!$data)
            return false;

        $out = array();

        if ($includeHeader) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $header) {
                $csvRow[$fieldCnt++] = ucwords(str_replace('_', ' ', $header));
            }
            $out[] = $csvRow;
        }

        foreach ($data as $row) {
            $csvRow = array();
            $fieldCnt = 0;
            foreach ($fields as $field) {
                $csvRow[$fieldCnt++] = $row[$field];
				if(($field == 'credit') && ($row['transaction_type'] == 'credit')){
					$csvRow[$fieldCnt-1] =	number_format((float)$row['transaction_amount'], 2, '.', '');
				}
				else if(($field == 'debit') && ($row['transaction_type'] == 'debit')){
					//$csvRow[$fieldCnt-1] =	$row['transaction_amount'];
					$csvRow[$fieldCnt-1] =	number_format((float)$row['transaction_amount'], 2, '.', '');
				}
            }
            $out[] = $csvRow;
        }

        /*echo '<pre>';
        if (is_array($out))
            print_r($out);
        else
            echo $out;
        echo '</pre>';*/

        $this->array_to_csv_download($out, $filename);

        return true;
    }


    public function generateHtmlReport($data, $fields, $slNo) {
        $out = '';
        if (!$data || !$fields)
            return 'No data found';

        $out = '<table class="table table-striped table-bordered">';

        $out .= '<thead>';
        $out .= '<tr>';
        $out .= '<th>SlNo</th>';
        foreach ($fields as $header) {
            $out .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
        }
        $out .= '</tr>';
        $out .= '</thead>';

        $out .= '<thead>';
        foreach ($data as $row) {
            $out .= '<tr>';
            $out .= '<td>' . $slNo++ . '</td>';
            foreach ($fields as $field) {
                $out .= '<td class="center">' . $row[$field] . '&nbsp;</td>';
            }
            $out .= '</tr>';
        }
        $out .= '</thead>';


        $out .= '</table>';

        return $out;
    }

    public function generateHtml($data, $tableHeaders, $slNo) {
        $out = '';
        if (!$data)
            return 'No data found';

        // initialize table
        $out = '<table class="table table-striped table-bordered">';

        // prepare table headers
        $out .= '<thead>';
        $out .= '<tr>';
        $out .= '<th>SlNo</th>';
        foreach ($tableHeaders as $header) {
            $out .= '<th>' . $header . '</th>';
        }
        $out .= '</tr>';
        $out .= '</thead>';

        // prepare table body
        $out .= '<tbody>';
        // loop through data
        //$slNo = 1;
        foreach ($data as $row) {
            $row = array_values($row);
            $out .= '<tr>';
            $out .= '<td>' . $slNo++ . '</td>';
            for ($fieldCnt = 0; $fieldCnt < (count($tableHeaders)); $fieldCnt++) {
                $out .= '<td class="center">' . $row[$fieldCnt] . '&nbsp;</td>';
            }
            $out .= '</tr>';
        }

        $out .= '</tbody>';

        // close table tag
        $out .= '</table>';

        return $out;
    }

    public function generateHtmlforPdf($data, $tableHeaders, $heading, $user='',$headerImageUrl='',$incomeReport = 0) {
        $out = '';
        if (!$data)
            return 'No data found';

        $out = '<html><head></head><body>';

        $out .= '<div align="center" class="box-body table-responsive">';
			$out .= '<table style="width:670px;margin: 0 auto; " cellspacing="0" cellpadding="4" >';
				$out .= '<tr>';
					$out .= '<td><img src="' . $headerImageUrl . '" style="width:300px;" /></td>';
					$out .= '<td>';
						if(isset($user) && $user) {
							$userRoles = array('','Super Administrator','Admin','Manager','Staff');
							$out .= '<h5>Generated By</h5>';
							$out .= '<p style="font-size:10px;">';
								$out .= $user['name'] . ' ( ' . $userRoles[$user['utype']] . ')<br>' ;
								$out .= $user['email'];
							$out .= '</p>';
						}
					$out .= '</td>';
				$out .= '</tr>';
			$out .= '</table>';

        $out .= '<table style="width:100%;margin: 0 auto; border:1px solid #000;" cellspacing="0" cellpadding="0" >';

        $out .= '<br/><tr style="border-bottom:1px solid #000;">';
        $out .= '<td align="left"></td>';
        $out .= '<td align="center"><h4 style="margin:0; padding:0;">' . $heading . '</h4></td>';
        $out .= '<td align="right"><span style="font-size:11px; margin-right:10px;"></span></td>';
        $out .= '<br/></tr>';

        $out .= '</table>';
        // initialize table
        $out .= '<table class="table table-striped table-bordered" style="width:100%;margin: 0 auto; border:1px solid #000;border-collapse: collapse;" >';

        // prepare table headers
        $out .= '<thead>';
        $out .= '<tr style="border:1px solid #000;" bgcolor="#F2F2F2">';
        //$out .= '<th align="center" style="border:1px solid #000; font-size:12px;">SlNo</th>';
        foreach ($tableHeaders as $header) {
            $out .= '<th align="center" style="border:1px solid #000; font-size:12px;">' . $header . '</th>';
        }
        $out .= '</tr>';
        $out .= '</thead>';

        // prepare table body
        $out .= '<tbody>';
        // loop through data
        $slNo = 1;
        if($incomeReport) $pageTotal = 0;
        foreach ($data as $row) {
			if($incomeReport) $pageTotal += $row['amount_in_qar'];
            $row = array_values($row);
            $out .= '<tr style="border:1px solid #000;padding:5px 0 5px 0;">';
            //$out .= '<th align="center" style="border:1px solid #000; font-size:12px;">SlNo</th>';
            for ($fieldCnt = 0; $fieldCnt < (count($tableHeaders)); $fieldCnt++) {
                $out .= '<td align="center" style="border:1px solid #000;">' . $row[$fieldCnt] . '</td>';
            }
            $out .= '</tr>';
        }
		if($incomeReport) {
			$out .= '<tr style="border:1px solid #000;padding:5px 0 5px 0;">';
				$out .= '<td align="right" style="border:1px solid #000;" colspan="5">Total</td>';
				$out .= '<td align="center" style="border:1px solid #000;">QAR ' . number_format(floatval($pageTotal),2) . '</td>';
			$out .= '</tr>';
		}
        $out .= '</tbody>';

        // close table tag
        $out .= '</table>';
        $out .= '<br/><p style="font-size:10px;">Downloaded On : ' . date('Y-m-d h:i A') . '</p>';
        $out .= '</div>';

        $out .= '</body></html>';
        //exit($out);
        return $out;

    }

    function authenticateUrl($controller, $action, $url, $idUser,$userType='') {
		if($userType == 1 ) return  true;

        // initialise necessary models
        $modelUrl = new Application_Model_DbTable_Urls();
        $modelAcl = new Application_Model_DbTable_Acl();

        // check if url exists
        $url = $modelUrl->getRowByCondition(' `controller` = "' . $controller . '" AND `action` = "' . $action . '" ');

        if (!$url)
            return false;
        else {
            // check if user has permission to access the url
            $userAcl = $modelAcl->getRowByCondition(' `id_admin_user` = ' . $idUser . ' AND `id_url` = ' . $url['id_url'] . ' AND `status` = 1');

		    if ($userAcl)
                return true;
            else
                return false;
        }

        return false;
    }

	function getUserMenu($idUser, $activeMenuItem, $baseUrl,$userType = '') {
		$out = array();
		$menuItem = array(); $subMenu = array(); $subMenuItem = array();

		/* --------------------Menu - Dashboard ------------------------ */
		$menuItem['title'] = 'Dashboard';
		$menuItem['id'] = 'dashboard';
		$menuItem['url'] = $baseUrl.'/backoffice/index';
		if($menuItem) {
			$out[] = $menuItem;
			$menu = array();
		}

		/* --------------------Menu - Users ------------------------ */
		if ($this->authenticateUrl('users', 'adduser', 'users/adduser', $idUser,$userType)) {
			$subMenuItem['title'] = 'Add User';
			$subMenuItem['url'] = $baseUrl . '/backoffice/users/adduser';
			$subMenu[] = $subMenuItem;
		}
		if ($this->authenticateUrl('users', 'index', 'users/index', $idUser,$userType)) {
			$subMenuItem['title'] = 'Users';
			$subMenuItem['url'] = $baseUrl . '/backoffice/users/index';
			$subMenu[] = $subMenuItem;
		}
		return $out;
	}

   function getMenu($idUser, $activeMenuItem, $baseUrl,$userType = '') {

	    $out = '';


		 /* --------------------Menu - Backoffice Users ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/createcategory">Add User Type</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/usercategories"> User Types</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/adduser"> Add Backoffice Users</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/adminusers"> Backoffice Users</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/addurl"> Add URL</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/urls"> URL List</a></li>';
        } else {
            if ($this->authenticateUrl('adminuser', 'createcategory', 'backoffice/adminuser/createcategory', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/createcategory"> Add User Type</a></li>';
            if ($this->authenticateUrl('adminuser', 'usercategories', 'backoffice/adminuser/usercategories', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/usercategories"> User Types</a></li>';
            if ($this->authenticateUrl('adminuser', 'adduser', 'backoffice/adminuser/adduser', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/adduser"> Add Backoffice Users</a></li>';
            if ($this->authenticateUrl('adminuser', 'adminusers', 'backoffice/adminuser/adminusers', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/adminusers"> Backoffice Users</a></li>';
            if ($this->authenticateUrl('adminuser', 'addurl', 'backoffice/adminuser/addurl', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/addurl"> Add URL</a></li>';
            if ($this->authenticateUrl('adminuser', 'urls', 'backoffice/adminuser/urls', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/adminuser/urls"> URL List</a></li>';
        }
        if ($menu != '') {
            $out .='<li ';
            if ($activeMenuItem == 'adminusers' || $activeMenuItem == 'url')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-group"></i>Backoffice Users<span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }


		 /* --------------------Menu - Users ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/users/register"> Customer Registration</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/users"> Customer List</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/users/addvehicletypes"> Add Customer Vehicle Type</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/users/vehicletypes"> Customer Vehicle Types</a></li>';
        } else {
            if ($this->authenticateUrl('users', 'register', 'backoffice/users/register', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/users/register"> Customer Registration</a></li>';
            if ($this->authenticateUrl('users', 'index', 'backoffice/users', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/users"> Customer List</a></li>';
			if ($this->authenticateUrl('users', 'addvehicletypes', 'backoffice/users/addvehicletypes', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/users/addvehicletypes"> Add Customer Vehicle Type</a></li>';
			if ($this->authenticateUrl('users', 'vehicletypes', 'backoffice/users/vehicletypes', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/users/vehicletypes"> Customer Vehicle Types</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'users')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-users"></i>Users & Vehicles<span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Owners ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/owner/addowner">Partner Registration</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/owner"> Partners List</a></li>';
        } else {
            if ($this->authenticateUrl('owner', 'addowner', 'backoffice/owner/addowner', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/owner/addowner">Partner Registration</a></li>';
            if ($this->authenticateUrl('owner', 'index', 'backoffice/owner', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/owner"> Partners List</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'drivers')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-user-secret"></i>Partners <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		 /* --------------------Menu - Drivers ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/driver/register"> Driver Registration</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/driver"> Drivers List</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/driver/unapproved"> Unapproved Drivers</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle/addrecoveryvehicle"> Add Recovery Vehicles</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle"> Recovery Vehicles List</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle/unapprovedvehicles"> Recovery Vehicles To Be Approved</a></li>';
        } else {
            if ($this->authenticateUrl('driver', 'register', 'backoffice/driver/register', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/driver/register"> Driver Registration</a></li>';
            if ($this->authenticateUrl('driver', 'index', 'backoffice/driver', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/driver"> Drivers List</a></li>';
			if ($this->authenticateUrl('driver', 'index', 'backoffice/driver/unapproved', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/driver"> Unapproved Drivers</a></li>';
            if ($this->authenticateUrl('recoveryvehicle', 'addrecoveryvehicle', 'backoffice/recoveryvehicle/addrecoveryvehicle', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle/addrecoveryvehicle"> Add Recovery Vehicles</a></li>';
            if ($this->authenticateUrl('recoveryvehicle', 'index', 'backoffice/recoveryvehicle', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle"> Recovery Vehicles List</a></li>';
			if ($this->authenticateUrl('recoveryvehicle', 'index', 'backoffice/recoveryvehicle/unapprovedvehicles', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/recoveryvehicle/unapprovedvehicles"> Recovery Vehicles To Be Approved</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'drivers' || $activeMenuItem == 'trucks')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-cab"></i>Drivers & Recovery Vehicles <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }



		 /* --------------------Menu - Services ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/services/addservice"> Add Service</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/services"> Services</a></li>';
        } else {
            if ($this->authenticateUrl('services', 'addservice', 'backoffice/services/addservice', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/services/addservice"> Add Service</a></li>';
            if ($this->authenticateUrl('services', 'urls', 'backoffice/services', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/services"> Services</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'services')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-wrench"></i>Services <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Workshops ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/workshop/addworkshop"> Add Garage</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/workshop"> Garages</a></li>';
        } else {
            if ($this->authenticateUrl('workshop', 'addworkshop', 'backoffice/recoveryvehicle/addworkshop', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/workshop/addworkshop"> Add Garage</a></li>';
            if ($this->authenticateUrl('workshop', 'index', 'backoffice/workshop', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/workshop"> Garages</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'workshops')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-industry"></i>Garages <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }


		/* --------------------Menu - Requests ------------------------ */
        $url = '';
        if ($userType == '1') {
            $url .=$baseUrl . '/backoffice/requests';
        } else {
            if ($this->authenticateUrl('requests', 'index', 'backoffice/requests', $idUser))
                $url .=$baseUrl . '/backoffice/requests';
        }
        if ($url != '') {
             $out .='<li ';
            if ($activeMenuItem == 'requests')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a href="' . $url . '"><i class="fa fa-retweet"></i>Service Requests </a>';
            $out .='</li>';
        }


		/* --------------------Menu - Toll Gates ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/toll/addtoll"> Add Toll Gate</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/toll"> Toll Gates</a></li>';
        } else {
            if ($this->authenticateUrl('toll', 'addtoll', 'backoffice/toll/addtoll', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/toll/addtoll"> Add Toll Gate</a></li>';
            if ($this->authenticateUrl('toll', 'index', 'backoffice/toll', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/toll"> Toll Gates</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'toll')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-road"></i>Toll Gates <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Rate Slab Locations ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/add-rateslab-location"> Add Rate Slab Locations</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab"> Rate Slab Locations</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/add-rateslab-distance"> Add Rate Slab By Distance</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/rate-slabs"> Rate Slab By Distance </a></li>';
		} else {
            if ($this->authenticateUrl('toll', 'addtoll', 'backoffice/rateslab/add-rateslab-location', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/add-rateslab-location"> Add Rate Slab Locations</a></li>';
            if ($this->authenticateUrl('toll', 'index', 'backoffice/rateslab', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab"> Rate Slab Locations</a></li>';
            if ($this->authenticateUrl('toll', 'addtoll', 'backoffice/rateslab/add-rateslab-distance', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/add-rateslab-distance"> Add Rate Slab By Distances</a></li>';
            if ($this->authenticateUrl('toll', 'index', 'backoffice/rateslab/rate-slabs', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/rateslab/rate-slabs"> Rate Slab Locations</a></li>';
	    }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'rateslab')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-dollar"></i>Rate Slab Locations <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Subcon(Partner) rateslabs ------------------------ */

        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/subcon/addsubcon">Add Partner Rateslab</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/subcon"> Partner Rateslabs List</a></li>';
        } else {
            if ($this->authenticateUrl('subcon', 'register', 'backoffice/subcon/addsubcon', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/subcon/addsubcon">Add Partner Rateslab</a></li>';
            if ($this->authenticateUrl('subcon', 'index', 'backoffice/subcon', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/subcon"> Partner Rateslabs List</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'rateslab')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-file-text"></i>Partner Rateslabs <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Messages ------------------------ */

        $menu = '';
		if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/messages/addtemplates"> Add Email Templates</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/messages"> Email Templates</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/messages/addsmstemplates"> Add SMS Templates</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/messages/smstemplates"> SMS Templates</a></li>';

        } else {
            if ($this->authenticateUrl('messages', 'addtemplates', 'backoffice/messages/addtemplates', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/messages/addtemplates"> Add Email Templates</a></li>';
            if ($this->authenticateUrl('messages', 'index', 'backoffice/messages', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/messages"> Email Templates</a></li>';
			if ($this->authenticateUrl('messages', 'addtemplates', 'backoffice/messages/addsmstemplates', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/messages/addsmstemplates"> Add SMS Templates</a></li>';
            if ($this->authenticateUrl('messages', 'index', 'backoffice/messages/smstemplates', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/messages/smstemplates"> SMS Templates</a></li>';

        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'message')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-envelope"></i> Templates <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
		}

		/* --------------------Menu - Requests ------------------------ */
        $url = '';
        if ($userType == '1') {
            $url .=$baseUrl . '/backoffice/notifications';
        } else {
            if ($this->authenticateUrl('notifications', 'index', 'backoffice/notifications', $idUser))
                $url .=$baseUrl . '/backoffice/notifications';
        }
        if ($url != '') {
             $out .='<li ';
            if ($activeMenuItem == 'notf')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a href="' . $url . '"><i class="fa fa-external-link"></i>Send Push Notification </a>';
            $out .='</li>';
        }

		/* --------------------Menu - Configurations ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addconfig"> Add Configurations</a></li>';
            $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations"> Configurations</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addvariables"> Add Dynamic Variables</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/variables"> Dynamic Variables</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addmessages"> Add Dynamic Messages</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/messages"> Dynamic Messages</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/menu/addmenu"> Add Dynamic Menu</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/menu/index"> Dynamic Menus</a></li>';
        } else {
            if ($this->authenticateUrl('configurations', 'register', 'backoffice/configurations/addconfig', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addconfig"> Add Configurations</a></li>';
            if ($this->authenticateUrl('configurations', 'index', 'backoffice/configurations', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations"> Configurations</a></li>';
			if ($this->authenticateUrl('configurations', 'addvariables', 'backoffice/configurations/addvariables', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addvariables"> Add Dynamic Variables</a></li>';
			if ($this->authenticateUrl('configurations', 'variables', 'backoffice/configurations/variables', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/variables"> Dynamic Variables</a></li>';
			if ($this->authenticateUrl('configurations', 'addmessages', 'backoffice/configurations/addmessages', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/addmessages"> Add Dynamic Messages</a></li>';
			if ($this->authenticateUrl('configurations', 'messages', 'backoffice/configurations/messages', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/configurations/messages"> Dynamic Messages</a></li>';
			if ($this->authenticateUrl('configurations', 'addmenu', 'backoffice/menu/addmenu', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/menu/addmenu"> Add Dynamic Menu</a></li>';
			if ($this->authenticateUrl('configurations', 'variables', 'backoffice/menu/index', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/menu/index"> Dynamic Menus</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'config')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-gear"></i>Configurations <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }

		/* --------------------Menu - Financial Reports ------------------------ */
        $menu = '';
        if ($userType == '1') {
            $menu .='<li><a href="' . $baseUrl . '/backoffice/reports/revenue"> Revenue Overview</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/reports/service-requests"> Service Requests</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/reports/toll-crossed-requests"> Toll Crossed Requests</a></li>';
			$menu .='<li><a href="' . $baseUrl . '/backoffice/reports/inactive-drivers"> Inactive Drivers</a></li>';
		} else {
            if ($this->authenticateUrl('reports', 'revenue', 'backoffice/reports/revenue', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/reports/revenue"> Revenue Overview</a></li>';
			if ($this->authenticateUrl('reports', 'service-requests', 'backoffice/reports/service-requests', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/reports/service-requests"> Service Requests</a></li>';
			if ($this->authenticateUrl('reports', 'toll-crossed-requests', 'backoffice/reports/toll-crossed-requests', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/reports/toll-crossed-requests"> Toll Crossed Requests</a></li>';
			if ($this->authenticateUrl('reports', 'inactive-drivers', 'backoffice/reports/inactive-drivers', $idUser))
                $menu .='<li><a href="' . $baseUrl . '/backoffice/reports/inactive-drivers"> Inactive Drivers</a></li>';
        }
        if ($menu != '') {
             $out .='<li ';
            if ($activeMenuItem == 'reports')
                $out .= 'class="active"';
            $out .='>';
            $out .='<a><i class="fa fa-file"></i>Reports <span class="fa fa-chevron-down"></span></a>';
            $out .='<ul class="nav child_menu">';
            $out .= $menu;
            $out .='</ul>';
            $out .='</li>';
        }


        return $out;
    }

    public function getHash($arr) {
        $salt = "HZDtC_ASFP_@2/>'";
        $data = $arr['id_transaction'] . $arr['id_account'] . $arr['transaction_type'] . $arr['transaction_amount']
                . $arr['transaction_date'] . $arr['transaction_description'] . $arr['comments'] . $arr['promo_code']
                . $arr['level'] . $arr['id_sale'] . $arr['current_balance'];

//        $data = json_encode($arr);
        $str = md5($data . $salt);
        //echo "encrypt: " . $str;
        return $str;
    }

}
