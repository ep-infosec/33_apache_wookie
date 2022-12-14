/*
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 * 
 *      http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * The ${widget.shortname}_controller object
 * This is used to wire up the view and model with actions
 */ 
var ${widget.shortname}_detail_controller = {
    init:function() {
    	var id = ${widget.shortname}_detail_controller.get("itemId");
    	if (id === undefined) {
	    id = Widget.preferences.getItem("itemId");
	    if (id === undefined) {
    		id = ${itemDetail.default.itemId}
	    }
    	}
	Widget.preferences.setItem("itemId", id);
        ${widget.shortname}_detail_controller.populate();
    },
    
   /**
    * Populate the results list with data for a given item.
    * The id of the item is obtained from  Widget.preferences.getItem("itemId");. 
    * The data is transformed using the stylesheet named in the itemDetail.xsl.url property.
    */
    populate:function() {
	var itemId = Widget.preferences.getItem("itemId");
	var url = widget.proxify(${itemDetail.get.url});
        $('#detail').remove();
        var html = ${widget.shortname}_detail_controller.transform(url);
        $('#content-primary').html(html).trigger("create");
    },

    /**
     * get a parameter from the URL
     */
    get:function(name){
	if(name=(new RegExp('[?&]'+encodeURIComponent(name)+'=([^&]*)')).exec(location.search))
	    return decodeURIComponent(name[1]);
    },
    
    /**
     * load XML data and transform it into HTML
     */
    transform:function(src){
     var output = "";

     $.ajax({
      url: src,
      dataType: "xml",
      async: false,
      success: function(xml){
        output = ${widget.shortname}_detail_controller.transformXml(xml); 
      }
     });
     
     return output;
    },

    /**
     * Transform data into HTML
     */ 
    transformXml:function(xml){
        
        var output = "";
        var items = "";
        
        /**
         * For each element that matches itemName, create a new ItemTemplate and
         * replace placeholders in the template with values from the XML using the ItemElements 
         * and ItemAttrobutes lists
         */
        $(xml).find(${browse.item.name}).each(
           function(){
             var item = "${browse.item.template}";
             var elements = ${browse.item.elements}.split(",");
             for (var i=0;i<elements.length;i++){
                var element = elements[i]; 
                var pattern = "\\$\{" + element.toUpperCase() +"\}";
                item = item.replace(new RegExp(pattern,'g'), $(this).find(element).first().text());
             }
             var attributes = ${browse.item.attributes}.split(",");
             for (var i=0;i<attributes.length;i++){
                var attribute = attributes[i]; 
                var pattern = '\\$\{'+attribute.toUpperCase()+'\}';
                item = item.replace(new RegExp(pattern,'g'), $(this).attr(attribute));
             }
             items += item;
           }
        );
        
        output = ${browse.collection.template}.replace("${ITEMS}", items);
        return output;
    }


}

/**
 * Initialise page
 */
$('#home').live('pageshow',function(event) {
   ${widget.shortname}_detail_controller.init(); 
});

