<?php
/** @package org.wookie.php */

/*
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 * limitations under the License.
 */

/** @ignore */
require("WookieConnectorExceptions.php");
require("WookieServerConnection.php");
require("WidgetInstances.php");
require("Widget.php");
require("WidgetInstance.php");
require("WidgetProperties.php");
require("User.php");
require("HTTP_Response.php");
require("Logger.php");
require("WookieConnectorServiceInterface.php");

/**
 * Wookie connector service, handles all the data requests and responses 
 * @package org.wookie.php 
 */

class WookieConnectorService implements WookieConnectorServiceInterface {
	private $conn;
	public  $WidgetInstances;
	private $user;
	private $httpStreamCtx;
	private $logger;
	private $locale;
	
	/** Create new connector
	 * 
	 * @param String url to Wookie host
	 * @param String Wookie API key
	 * @param String shareddatakey to use
	 * @param String user login name
	 * @param String user display name
	 */

	function __construct($url, $apiKey, $sharedDataKey, $loginName, $screenName = null) {
		$this->setConnection(new WookieServerConnection($url, $apiKey, $sharedDataKey));
		$this->setWidgetInstancesHolder();
		$this->setUser($loginName, $screenName);
		$this->setHttpStreamContext(array('http' => array('timeout' => 15)));
		$this->logger = new Logger("");
	}

	/** Initiate logger
	 * @param String path to writeable folder 
	 */
	
	public function setLogPath($path) {
    	$this->getLogger()->setPath($path);
	}

	/** Get logger
	 * @return Logger Simple logger for ConnectorService
	 */
	
	private function getLogger() {
    	return $this->logger;
	}
	
	/** Set locale */
	
	public function setLocale($locale) {
		$this->locale = (string) $locale;
	}
	
	/** Get locale */
	
	public function getLocale() {
		return $this->locale;
	}
	
	/** Set Wookie connection
	 * 
	 * @param WookieServerConnection new WookieServerConnection instance
	 */
	private function setConnection($newConn) {
		$this->conn = $newConn;
	}
	
	/** Get current Wookie connection
	 * @return WookieServerConnection current Wookie server connection
	 */

	public function getConnection() {
		return $this->conn;
	}
	
	/** Set WidgetInstances holder */
	private function setWidgetInstancesHolder() {
		$this->WidgetInstances = new WidgetInstances();
	}

	/** Set user for connection
	 * 
	 * @param String username
	 * @param String optional display name
	 */
	public function setUser($loginName, $screenName = null) {
		if($screenName == null) {
			$screenName = $loginName;
		}
		$this->user = new User($loginName, $screenName);
	}
	
	/** Get current user
	 * @return User current connection user
	 */
	public function getUser() {
		return $this->user;
	}
	
	/** Set HttpStreamContext parameters
	 * 
	 * @param Array array of context parameters
	 */
	private function setHttpStreamContext($params) {
		$this->httpStreamCtx = @stream_context_create($params);
	}
	
	/** Get HttpStreamContext
	 * @return StreamContextResource HttpStreamContext resource
	 */
	
	private function getHttpStreamContext() {
		return $this->httpStreamCtx;
	}

	/** Do HTTP request
	 * @param String url to request
	 * @param String data to send
	 * @param String method to use
	 * @return HTTP_Response new HTTP_Response instance 
	 */

	private function do_request($url, $data, $method = 'POST')
	{
		if(is_array($data)) {
		 // convert variables array to string:
			$_data = array();
			while(list($n,$v) = each($data)){
				$_data[] = urlencode($n)."=".urlencode($v);
			}
			$data = implode('&', $_data);
		}

		$params = array('http' => array(
                  'method' => $method,
                  'content' => $data,
     			  'timeout' => 15
		));
		$this->setHttpStreamContext($params);
		$response = @file_get_contents($url, false, $this->getHttpStreamContext());
		
		//revert back to default value for other requests
		$this->setHttpStreamContext(array('http' => array('timeout' => 15)));

		return new HTTP_Response($response, $http_response_header);
	}

	/**
	 * 
	 * @param String $widgetFile - full path on disk where the widget is located
	 * @param unknown $adminUsername - wookie admin username
	 * @param unknown $adminPassword - wookie admin password
	 * @return HTTP_Response - new HTTP_Response instance
	 */
	public function postWidget($widgetFile, $adminUsername, $adminPassword){
		$file = basename($widgetFile); //actual filename without the path
		$fileContents = file_get_contents($widgetFile);
		//Boundary definition
		$boundary = substr(md5(rand(0,32000)), 0, 20);
		$data = "--".$boundary . "\r\n";		
		$data .= "Content-Disposition: form-data; name=\"upload\"; filename=\"" . $file . "\"\r\n";
		$data .= "Content-Type: application/octet-stream; charset=ISO-8859-1\r\n";
		$data .= "Content-Transfer-Encoding: binary\r\n\r\n";
		$data .= $fileContents."\r\n";
		$data .= "--".$boundary. "--" . "\r\n";
		//Construct params
		$params = array('http' => array(
				'method' => 'POST',
				'header' => "Authorization: Basic " . base64_encode("$adminUsername:$adminPassword") . "\r\n" .
							//"Connection: Keep-Alive" . "\r\n" .
							"Content-Type: multipart/form-data;boundary=".$boundary ."\r\n",
				'content' => $data,
				'timeout' => 30
		));
		$this->setHttpStreamContext($params);
		$requestUrl = $this->getConnection()->getURL().'widgets';
		$response = @file_get_contents($requestUrl, false, $this->getHttpStreamContext());
		//revert back to default value for other requests
		$this->setHttpStreamContext(array('http' => array('timeout' => 15)));
		return new HTTP_Response($response, $http_response_header);
	}
	
	/**
	 * 
	 * @param String $requestUrl - a string url of where the widget is online
	 * @param unknown $adminUsername - wookie admin username
	 * @param unknown $adminPassword - wookie admin password
	 * @throws WookieConnectorException
	 * @return string - *should be the xml representation of the widget* - but for now a raw dump of the response by wookie
	 */
	public function postWidgetByUrl($requestUrl, $adminUsername, $adminPassword){
		if(!isset($adminUsername) || !isset($adminUsername)){
			throw new WookieConnectorException("Wookie admin username and password missing.");
		}
		$response = $this->do_request($requestUrl, null, 'GET');
		if($response->getStatusCode() == 200) {
			$filename = uniqid(rand(), true) . '.wgt';
			$handle = fopen(sys_get_temp_dir()  . $filename, "w");
			fwrite($handle, $response->getResponseText());
			$widgetResponse = $this->postWidget(sys_get_temp_dir() . $filename, $adminUsername, $adminPassword);
			fclose($handle);
			unlink(sys_get_temp_dir() . $filename);
			if($response->getStatusCode() == 200 || $response->getStatusCode() == 201){
				return simplexml_load_string($widgetResponse->getResponseText());
			}
			else{
				throw new WookieConnectorException("Problem uploading the widget to " . $this->getConnection()->getURL().'widgets');
			}
		}
		else{
			throw new WookieConnectorException("Problem downloading the original widget from " . $requestUrl);
		}
	}

	/**
	 * Get or create an instance of a widget.
	 *
	 * @param Widget|String instance of widget or guid
	 * @return WidgetInstance WidgetInstance if successful, otherwise false
	 * @throws WookieConnectorException
	 */

	public function getOrCreateInstance($Widget_or_GUID) {
		try {
			if($Widget_or_GUID instanceof Widget) {
				$guid = $Widget_or_GUID->getIdentifier();
			} else {
				$guid = $Widget_or_GUID;
			}
			if($guid == '') {
				throw new WookieConnectorException("No GUID nor Widget object");
			}
			$requestUrl = $this->getConnection()->getURL().'widgetinstances';
			$request.= '&api_key='.$this->getConnection()->getApiKey();
			$request.= '&userid='.$this->getUser()->getLoginName();
			$request.= '&shareddatakey='.$this->getConnection()->getSharedDataKey();
			$request.= '&widgetid='.$guid;
		    if($locale = $this->getLocale()) {
                $request .= '&locale='.$locale;
            }

			if(!$this->checkURL($requestUrl)) {
				throw new WookieConnectorException("URL for supplied Wookie Server is malformed: ".$requestUrl);
			}
			$response = $this->do_request($requestUrl, $request);

			//if instance was created, perform second request to get widget instance
			if($response->getStatusCode() == 201) {
				$response = $this->do_request($requestUrl, $request);
			}
			if($response->getStatusCode() == 401) { throw new WookieConnectorException("Invalid API key"); }

			$instance = $this->parseInstance($guid, $response->getResponseText());
			if($instance) {
			  $this->WidgetInstances->put($instance);
			
			  //add current user as participant
			  $this->addParticipant($instance, $this->getUser());
			}
			return $instance;
		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		}
		return false;
	}


	/**
	 * Record an instance of the given widget.
	 *
	 * @param String widget guid
	 * @param String xml description of the instance as returned by the widget server when the widget was instantiated.
	 * @return new Widget instance or false
	 */
	private function parseInstance($widgetGuid, $xml) {
		$xmlWidgetData = @simplexml_load_string($xml);
		if($xmlWidgetData instanceof SimpleXMLElement) {
			$url = (string) $xmlWidgetData->url;
			$title = (string) $xmlWidgetData->title;
			$height = (string) $xmlWidgetData->height;
			$width = (string) $xmlWidgetData->width;
			$instance = new WidgetInstance($url, $widgetGuid, $title, $height, $width);
			return $instance;
		}
		return false;
	}

	/**
	 * Check if URL is parsable.
	 *
	 * @param String url to parse
	 * @return boolean true if parseable, otherwise false
	 */

	private function checkURL($url) {
		$UrlCheck = @parse_url($url);
		if($UrlCheck['scheme'] != 'http' || $UrlCheck['host'] == null || $UrlCheck['path'] == null) {
			return false;
		}
		return true;
	}

	/**
	 * Add new participant
	 * @param WidgetInstance instance of WidgetInstance
	 * @param User instance of User
	 * @return boolean true - if added/exists - false if some error
	 * @throws WookieConnectorException
	 * @throws WookieWidgetInstanceException
	 */

	public function addParticipant($widgetInstance, $User)  {
		$Url = $this->getConnection()->getURL().'participants';

		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			if(!$User instanceof User) throw new WookieConnectorException('No User object');

			$data = array(
				'api_key' => $this->getConnection()->getApiKey(),
				'shareddatakey' => $this->getConnection()->getSharedDataKey(),
				'userid' => $this->getUser()->getLoginName(),
				'widgetid' => $widgetInstance->getIdentifier(),
				'participant_id' => $this->getUser()->getLoginName(),
				'participant_display_name' => $User->getScreenName(),
				'participant_thumbnail_url' => $User->getThumbnailUrl(),
				'participant_role' => '' // TODO fix actual roles - (API change) Uncomment this to use wookie 0.13.1 onwards
			);

			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Participants rest URL is incorrect: ".$Url);
			}

			$response = $this->do_request($Url, $data);
			$statusCode = $response->getStatusCode();

			switch($statusCode) {
		  case 200: //participant already exists
		  	return true;
		  	break;
		  case 201:
		  	return true; //new participant added
		  	break;
		  case ($statusCode > 201):
		  	throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());
		  	break;
			}

		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		}
	return false;
	}

	/**
	 * Delete participant
	 * @param WidgetInstance  instance of WidgetInstance
	 * @param User instance of User
	 * @return boolean true - if deleted, false - if not found
	 * @throws WookieConnectorException
	 * @throws WookieWidgetInstanceException
	 */

	public function deleteParticipant($widgetInstance, $User)  {
		$Url = $this->getConnection()->getURL().'participants';

		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			if(!$User instanceof User) throw new WookieConnectorException('No User object');

			$request = '?api_key='.$this->getConnection()->getApiKey();
			$request .= '&shareddatakey='.$this->getConnection()->getSharedDataKey();
			$request .= '&userid='.$this->getUser()->getLoginName();
			$request .= '&widgetid='.$widgetInstance->getIdentifier();
			$request .= '&participant_id='.$User->getLoginName();


			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Participants rest URL is incorrect: ".$Url);
			}

			$response = $this->do_request($Url.$request, false, 'DELETE');
			$statusCode = $response->getStatusCode();

		switch($statusCode) {
		  case 200: //participant deleted
		  	return true;
		  	break;
		  case 404:
		  	return false; //participant not found
		  	break;
		  case ($statusCode > 201):
		  	throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());
		  	break;
		}

		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		}
		return false;
	}

	/**
	 * Get the array of users for a widget instance
	 * @param WidgetInstance instance of WidgetInstance
	 * @return Array an array of users
	 * @throws WookieConnectorException
	 * @throws WookieWidgetInstanceException
	 */
	public function getUsers($widgetInstance) {
		$Url = $this->getConnection()->getURL().'participants';
		$Users = array();
		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			$request = '?api_key='.$this->getConnection()->getApiKey();
			$request .= '&shareddatakey='.$this->getConnection()->getSharedDataKey();
			$request .= '&userid='.$this->getUser()->getLoginName();
			$request .= '&widgetid='.$widgetInstance->getIdentifier();

			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Participants rest URL is incorrect: ".$Url);
			}

			$response = new HTTP_Response(@file_get_contents($Url.$request, false, $this->getHttpStreamContext()), $http_response_header);
			if($response->getStatusCode() > 200) throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());

			$xmlObj = @simplexml_load_string($response->getResponseText());

			if($xmlObj instanceof SimpleXMLElement) {
				foreach($xmlObj->children() as $participant) {
					$participantAttr = $participant->attributes();

					$id = (string) $participantAttr->id;
					$name = (string) $participantAttr->display_name;
					$thumbnail_url = (string) $participantAttr->thumbnail_url;

					$newUser = new User($id, $name, $thumbnail_url);
					array_push($Users, $newUser);
				}
			} else {
				throw new WookieConnectorException('Problem getting participants');
			}

		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		}
		return $Users;
	}



	/**
	 * Get a set of all the available widgets in the server. If there is an error
	 * communicating with the server return an empty set, or the set received so
	 * far in order to allow the application to proceed. The application should
	 * display an appropriate message in this case.
	 *
	 * @return array array of available widgets
	 * @throws WookieConnectorException
	 */

	public function getAvailableWidgets() {
		$widgets = array();
		try {
			$request = $this->getConnection()->getURL().'widgets?all=true';
			if($locale = $this->getLocale()) {
				$request .= '&locale='.$locale;
			}
			if(!$this->checkURL($request)) {
				throw new WookieConnectorException("URL for Wookie is malformed");
			}

			$response = new HTTP_Response(@file_get_contents($request, false, $this->getHttpStreamContext()), $http_response_header);
			$xmlObj = @simplexml_load_string($response->getResponseText());

			if($xmlObj instanceof SimpleXMLElement) {
				foreach($xmlObj->children() as $widget) {
				 $id = (string) $widget->attributes()->id;
				 $title = (string) $widget->title;
				 $description = (string) $widget->description;
				 $iconURL = (string) $widget->attributes()->icon;
				 if($iconURL == '') {
						$iconURL = (string) 'http://www.oss-watch.ac.uk/images/logo2.gif';
					}
					$Widget = new Widget($id, $title, $description, $iconURL);
					$widgets[$id] = $Widget;
				}
			} else {
				throw new WookieConnectorException('Problem getting available widgets');
			}

	 } catch(WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		}
		return $widgets;
	}

	/**
	 * Set property for Widget instance
	 * @param WidgetInstance instance of WidgetInstance
	 * @param Propety instance of Property
	 * @return Property new Property instance
	 * @throws WookieConnectorException, WookieWidgetInstanceException
	 */

	public function setProperty($widgetInstance, $propertyInstance) {
		$Url = $this->getConnection()->getURL().'properties';

		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			if(!$propertyInstance instanceof Property) throw new WookieConnectorException('No properties instance');

			$data = array(
				'api_key' => $this->getConnection()->getApiKey(),
				'shareddatakey' => $this->getConnection()->getSharedDataKey(),
				'userid' => $this->getUser()->getLoginName(),
				'widgetid' => $widgetInstance->getIdentifier(),
				'propertyname' => $propertyInstance->getName(),
				'propertyvalue' => $propertyInstance->getValue(),
				'is_public' => $propertyInstance->getIsPublic(),
			);

			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Properties rest URL is incorrect: ".$Url); 
			}

			$response = $this->do_request($Url, $data);
			$statusCode = $response->getStatusCode();

		switch($statusCode) {
		  case 201:
		  	return $propertyInstance; //new property added, let's return initial Property instance
		  	break;
		  case ($statusCode != 201):
		  	throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());
		  	break;
			}

		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		}
		return false;
	}

	/**
	 * Get property for Widget instance
	 * @param WidgetInstance instance of WidgetInstance
	 * @param Propety instance of Property
	 * @return Property if request fails, return false;
	 * @throws WookieConnectorException, WookieWidgetInstanceException
	 */

	public function getProperty($widgetInstance, $propertyInstance) {
		$Url = $this->getConnection()->getURL().'properties';

		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			if(!$propertyInstance instanceof Property) throw new WookieConnectorException('No properties instance');

			$data = array(
				'api_key' => $this->getConnection()->getApiKey(),
				'shareddatakey' => $this->getConnection()->getSharedDataKey(),
				'userid' => $this->getUser()->getLoginName(),
				'widgetid' => $widgetInstance->getIdentifier(),
				'propertyname' => $propertyInstance->getName()
			);
			$request = @http_build_query($data);

			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Properties rest URL is incorrect: ".$Url);
			}

			$response = new HTTP_Response(@file_get_contents($Url.'?'.$request, false, $this->getHttpStreamContext()), $http_response_header);
			$statusCode = $response->getStatusCode();
			if($statusCode != 200) {
				throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());
			}
			return new Property($propertyInstance->getName(), $response->getResponseText());

		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		}
		return false;
	}

	/**
	 * Delete property for Widget instance
	 * @param WidgetInstance instance of WidgetInstance
	 * @param Propety instance of Property
	 * @return boolean true/false -- true if deleted, false if doesnt exist
	 * @throws WookieConnectorException, WookieWidgetInstanceException
	 */

	public function deleteProperty($widgetInstance, $propertyInstance) {
		$Url = $this->getConnection()->getURL().'properties';

		try {
			if(!$widgetInstance instanceof WidgetInstance) throw new WookieWidgetInstanceException('No Widget instance');
			if(!$propertyInstance instanceof Property) throw new WookieConnectorException('No properties instance');

			$request = '?api_key='.$this->getConnection()->getApiKey();
			$request .= '&shareddatakey='.$this->getConnection()->getSharedDataKey();
			$request .= '&userid='.$this->getUser()->getLoginName();
			$request .= '&widgetid='.$widgetInstance->getIdentifier();
			$request .= '&propertyname='.$propertyInstance->getName();

			if(!$this->checkURL($Url)) {
				throw new WookieConnectorException("Properties rest URL is incorrect: ".$Url);
			}

			$response = $this->do_request($Url.$request, false, 'DELETE');
			$statusCode = $response->getStatusCode();
			
			if($statusCode != 200 && $statusCode != 404) {
				throw new WookieConnectorException($response->headerToString().'<br />'.$response->getResponseText());
			}
			if($statusCode == 404) {
				return false;
			}
			return true;

		} catch (WookieConnectorException $e) {
			$this->getLogger()->write($e->toString());
		} catch (WookieWidgetInstanceException $e) {
			$this->getLogger()->write($e->toString());
		}
		return false;
	}

}
?>
