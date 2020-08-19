# wpda-cwg-extensions
Extension for WordPress Data Access by Charles Godwin

__This plug in does NOT work with the premium version of WP Data Access__

2020/02/19 Version 1.0.1

Revised treatment of tokens to unescape the single quote like O'Brian

2020/02/05 Version 1.0.0

__Introduction__
This is an alternative search algorythm that looks for the occurance of each token in the search list rather than the default of just one string. 

__How to install__

This is not a normal WP plugin installation.

* create a folder in wp-content/plugins/wpda-cwg-extensions
* copy plugin/wpda-cwg-extensions.php to that folder
* enable the plugin

__How to search__

You can enter multiple words in the search box. Separate the words with spaces. The search will examine EACH record in the database for the existence of all the words. For example, if you type in the words __john__ __smith__, it will check each record to see if it contains the value __john__ AND the value __smith__. __JOHN__ is the same as __john__. Using __John smith__ is the same as __smith john__. The words you enter will be found in longer words. For example, it will find __john__ in Johnston and __smith__ in Smithsonian. The searched words may not all be in the same field in the record. If you want to search for a phrase or a word with spaces then put the value in double quotes. Such as __“Saint Mary”__ 
or __“Le Moine”__.
