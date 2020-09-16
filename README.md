# wpda-cwg-extensions
Extension for WordPress Data Access by Charles Godwin

Refer to CHANGES.md for changes
=======

__This plugin works with both the premium version and free version of WP Data Access__

__Introduction__
This is an alternative search algorithm that looks for the occurrence of each token in the search list rather than the default of just one string. 

__How to install__

A script named build-zip.sh is provided to build a plugin compatible zip file. Or you can follow these steps:

* create a folder wp-content/plugins named wpda-cwg-extensions  
 (wp-content/plugins/wpda-cwg-extensions)
* copy wpda-cwg-extensions.php to that folder
* enable the plugin

__How to search__

You can enter multiple words in the search box. Separate the words with spaces. The search will examine EACH record in the database for the existence of all the words. For example, if you type in the words __john__ __smith__, it will check each record to see if it contains the value __john__ AND the value __smith__. __JOHN__ is the same as __john__. Using __John smith__ is the same as __smith john__. The words you enter will be found in longer words. For example, it will find __john__ in Johnston and __smith__ in Smithsonian. The searched words may not all be in the same field in the record. If you want to search for a phrase or a word with spaces, then put the value in double quotes. Such as __“Saint Mary”__ 
or __“Le Moine”__.

__Premium Version Search__  

This plugin follows the premium version protocol of searching and all fields marked as Queryable are searched. Note that numeric fields are only searched  if the token is a number. A numeric search is for __field = floatval($token)__. All other fields use the MySQL __field LIKE "%token%"__ construct.
