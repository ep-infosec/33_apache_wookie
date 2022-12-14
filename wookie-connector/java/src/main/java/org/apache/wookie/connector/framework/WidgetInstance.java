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
 * An instance of a widget for use on the client.
 * 
 * @refactor this class duplicates code in the widget bean o nthe server side
 *
 */
public class WidgetInstance {
  String url;

  String id;
  String idKey;
  String title;
  String height;
  String width;

  /**
   * @deprecated This constructor is for backwards compatibility only; new implementations should
   * use the constructor with the ID Key parameter supplied
   * @param url
   * @param id
   * @param title
   * @param height
   * @param width
   */
  public WidgetInstance(String url, String id, String title, String height,
      String width) {
    setId(id);
    setUrl(url);
    setTitle(title);
    setHeight(height);
    setWidth(width);
  }
  
  public WidgetInstance(String url, String id, String title, String height,
      String width, String idKey) {
    setId(id);
    setUrl(url);
    setTitle(title);
    setHeight(height);
    setWidth(width);
    setIdKey ( idKey );
  }
  
  
  public String toString ( ) {
	  return "id: "+id+"  idKey:"+idKey+"  title: "+title+"  height: "+height+"  width: "+width;
  }
  
  public String getUrl() {
    return url;
  }

  public void setUrl(String url) {
    this.url = url;
  }

  public String getId() {
    return id;
  }

  public void setId(String id) {
    this.id = id;
  }
  
  public String getIdKey() {
	  return idKey;
  }
  
  
  public void setIdKey ( String idKey )
  {
	  this.idKey = idKey;
  }

  public String getTitle() {
    return title;
  }

  public void setTitle(String title) {
    this.title = title;
  }

  public String getHeight() {
    return height;
  }

  public void setHeight(String height) {
    this.height = height;
  }

  public String getWidth() {
    return width;
  }

  public void setWidth(String width) {
    this.width = width;
  }

}
