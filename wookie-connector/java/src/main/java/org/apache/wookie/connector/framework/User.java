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
package org.apache.wookie.connector.framework;

/**
 * A user represents a possible user of a widget. This class provides a standard way
 * of representing users in plugins for host environments.
 */ 
public class User {
  private String loginName = "UNKNOWN";
  private String screenName = "UNKNOWN";
  private String thumbnailUrl = "";
  private String role = null;
  
  /*
   * The preferred locale of the user, as a BCP-47 language tag
   */
  private String locale = null;
  
  /**
   * Create a new user.
   * 
   * @param loginName
   * @param screenName
   */
  public User(String loginName, String screenName) {
    setLoginName(loginName);
    setScreenName(screenName);
  }
  
  /**
   * Create a new user.
   * 
   * @param loginName
   * @param screenName
   * @param thumbnailUrl
   */
  public User(String loginName, String screenName, String thumbnailUrl) {
    setLoginName(loginName);
    setScreenName(screenName);
    setThumbnailUrl(thumbnailUrl);
  }
  
  /**
   * Create a new user.
   * 
   * @param loginName
   * @param screenName
   * @param thumbnailUrl
   * @param isHost
   */
  public User(String loginName, String screenName, String thumbnailUrl, String role) {
    setLoginName(loginName);
    setScreenName(screenName);
    setThumbnailUrl(thumbnailUrl);
    setRole(role);
  }
  
  /**
   * Create a new user.
   * 
   * @param loginName
   * @param screenName
   * @param thumbnailUrl
   * @param isHost
   */
  public User(String loginName, String screenName, String thumbnailUrl, String role, String locale) {
    setLoginName(loginName);
    setScreenName(screenName);
    setThumbnailUrl(thumbnailUrl);
    setRole(role);
    setLocale(locale);
  }

  /**
   * Get the login name for this user.
   */
  public String getLoginName() {
    return loginName;
  }

  /**
   * Get the screen name for this user. This is the name that is intended to be displayed on
   * screen. In many cases it will be the same as the login name.
   */
  public String getScreenName() {
    return screenName;
  }

  /**
   * Set the login name for this user. This is the value that is used by the user to register on the
   * system, it is guaranteed to be unique.
   * 
   * @param loginName
   */
  public void setLoginName(String loginName) {
    this.loginName = loginName;
  }

  /**
   * Set the screen name for this user. this is the value that should be displayed on screen.
   * In many cases it will be the same as the login name.
   * 
   * @param screenName
   */
  public void setScreenName(String screenName) {
    this.screenName = screenName;
  }
  
  /**
   * Set the thumbnail URL for this user. This is the src value for an image used to represent the user
   * @param thumbnailUrl
   * 
   * TODO validate the URL
   */
  public void setThumbnailUrl(String thumbnailUrl){
    //
    // Never set to null, only to an empty string
    //
    if (thumbnailUrl == null) thumbnailUrl = "";
    this.thumbnailUrl = thumbnailUrl;
  }

  /**
   * Get the URL for a thumbnail representing this user.
   * @return
   */
  public String getThumbnailUrl() {
    return this.thumbnailUrl;
  }

  /**
   * @return the role
   */
  public String getRole() {
	  return role;
  }

  /**
   * @param role the role to set
   */
  public void setRole(String role) {
	  this.role = role;
  }

  /**
   * @return the locale
   */
  public String getLocale() {
	  return locale;
  }

  /**
   * @param locale the locale to set
   */
  public void setLocale(String locale) {
	  this.locale = locale;
  }
  
  

}
