WebXtractor library goal is create a feed of most relevant list 
of items from any url. 

WebXtractor also attempts to navigate a limited set of subsequent urls as 
well (e.g. down a numbered navigation list)

WebXtractor items consists of title, short summary, a link to detailed 
information, and optionally an image (thumb).

WebXtractor item feeds can thus automatically be composed from newsfeeds, 
blog listings, shop catalogs, but also image galleries such as on flickr.

Included are a couple of sample scripts showing how to use the library:

* sample_gallery.php
  collecting & displaying only the gallery thumbs from a flickr 
  pool, thereby navigating a couple of pool pages (1, 2, 3..)
 
* sample_gallery2.php
  displaying while collecting only the gallery thumbs from a flickr 
  pool, thereby navigating a couple of pool pages (1, 2, 3..)
  
* sample_comicsforsale.php
  collecting a list of comic books for sale from 2nd hand shop site,
  thereby navigating a couple of listing pages 
  
  displaying and matching the collected titles against a predefined 
  catalog, using a very basic utility function  (needs to be extended)
  