# wpda-cdg-extensions
Extended Search Extension for WP Data Access by Charles Godwin

Refer to CHANGES.md for changes
=======

__This plugin works with both the premium version and free version of WP Data Access__

__Introduction__
This is an alternative search algorithm that looks for the occurrence of each space delimited token in the search string rather than the default of just one string. 

__How to install__

Download the repository zip file from https://github.com/CharlesGodwin/wpda-cwg-extensions. Use the standard WordPress process to upload and add a plugin.

__How to search__

You can enter multiple words in the search box. Separate the words with spaces. The search will examine EACH record in the database for the existence of all the words. For example, if you type in the words __john__ __smith__, it will check each record to see if it contains the value __john__ AND the value __smith__. __JOHN__ is the same as __john__. Using __John smith__ is the same as __smith john__. The words you enter will be found in longer words. For example, it will find __john__ in Johnston and __smith__ in Smithsonian. The searched words may not all be in the same column in the record. If you want to search for a phrase or a word with spaces, then put the value in double quotes. Such as __“Saint Mary”__ 
or __“Le Moine”__.

**_Column Filter_**

Column filter will accept special keyword EMPTY and NOTEMPTY. They must be in upper case. The plugin will test for column is NULL or column = '' for **EMPTY**, and column is NOT NULL and column != '' for **NOTEMPTY**.

Column searches are not tokenized and the entire string, as entered, is used for a search.

Note that numeric columns are only searched if the token is a number. A numeric search is for an equal match of either integer or float, depending on the type of column. Most other columns use the MySQL __column LIKE "%token%"__ construct.

__Premium Version Search__  

This plugin follows the premium version protocol of searching, and only columns marked as Queryable are searched. 

The plugin will only work if search mode is set to Normal wildcard search.
