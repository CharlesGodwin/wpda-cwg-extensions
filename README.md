# wpda-cwg-extensions
Extension for WordPress Data Access 

2020/02/05 Version 1.0.0

__How to install__

This is not a normal WP plugi installation..

* create a folder in wp-contet/plugins/wpda-cwg-extensions
* copy plugin/wpda-cwg-extensions.php to tht folder
* enable the plugin

__How to search__

You can enter multiple words in the search box. Separate the words with spaces. The search will examine EACH record in the database for the existence of all the words. For example, if you type in the words john smith, it will check each record to see if it contains the value john AND the value smith. JOHN is the same as john. Using John smith is the same as smith john. The words you enter will be found in longer words. For example, it will find john in Johnston and smith in Smithsonian. The searched words may not all be in the same field in the record. If you want to search for a phrase or a word with spaces then put the value in double quotes. Such as “Saint Mary” or “Le Moine”.
