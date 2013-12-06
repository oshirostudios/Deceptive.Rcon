// This needs to be in the global scope so we can use it in other functions.
var xml_obj;
/*
 
 We will be sending loadXMLDoc the url of the page we want to call,
 the query string to post to the url, and a function to call when
 the script updates.
 
*/
function LoadXMLDoc(url,query_string,callback) {
	xml_obj = false;
    // Try native XMLHttpRequest first.
    if(window.XMLHttpRequest) {
    	try {
			xml_obj = new XMLHttpRequest();
        } catch(e) {
			xml_obj = false;
        }
    // If native fails, fall back to IE's ActiveX objects.
    } else if(window.ActiveXObject) {
       	try {
        	xml_obj = new ActiveXObject("Msxml2.XMLHTTP");
      	} catch(e) {
        	try {
          		xml_obj = new ActiveXObject("Microsoft.XMLHTTP");
        	} catch(e) {
          		xml_obj = false;
        	}
		}
    }
	if(xml_obj) {
		// Any time the state of the request changes, callback will be called.
		xml_obj.onreadystatechange = callback;
		// Set the parameters of the request.
		// POST can also be GET.  We use the URL from above.
		// The <q>true</q> tells whether the request should be asynchronous.
		xml_obj.open("POST", url, true);
		xml_obj.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
		// Since we are POSTing, we send the query string as a header
		// instead of as a string at the end of the URL.
		xml_obj.send(query_string);
	}
	else{
		alert("Failed to create XMLHttpRequest!");
	}
}
