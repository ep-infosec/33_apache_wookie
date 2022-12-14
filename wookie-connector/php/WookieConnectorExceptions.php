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

/** Class for handling exception information 
 * @package org.wookie.php
 **/

class ExceptionString {
	private $str;
	
	/**
	 * Constructor for exception data handling
	 * @param Exception $ex
	 */
	function __construct($ex) {
		$this->str = @basename($ex->getFile()).':'.$ex->getLine().'::'.$ex->getMessage().'';
	}
	
	/** Return exception data as string
	 * @return String exception information
	 */
	public function getString() {
		return (string) $this->str;
	}
}

/** WookieConnectorException class 
 * @package org.wookie.php
 */

class WookieConnectorException extends Exception {
	
	/** Convert exception data to String
	 * @return String exception information
	 */

	public function toString() {
		$exStr = new ExceptionString($this);
		return $exStr->getString();
	}
}

/** WookieWidgetInstanceException class 
 * @package org.wookie.php
 */
class WookieWidgetInstanceException extends Exception {
	
	/** Convert exception data to String
	 * @return String exception information
	 */
	
	public function toString() {
		$exStr = new ExceptionString($this);
		return $exStr->getString();
	}
}

?>
