### ChangeLog for  wpda-xs (formerly wpda-cwg-extensions)

#### 1.9.0 2021/03/08
New: 
- Column filter will accept special keyword EMPTY and NOTEMPTY. They must be in upper case. 
      The plugin will test for column is NULL or column = '' for EMPTY, and column is NOT NULL and column != ''..
- Setting page added to Settings menu in dashboard. This allows you to temporarily disable the plugin and to set list of tables that should not be processed with the plugin.

Fixed: 
- Performance improvements.

Changed:
- Significant refactoring.
- Install process simplified.

Warning:
- The next release will rename the project and the plugin folder name
  
#### 1.2.0 / 2021/02/24
Added: Ability to exclude specific tables from where clause generation
Fixed: Enum type data was not being checked for column filter
#### 1.1.1 / 2020/09/17

Enhancement: Added check to only construct where clause if the search mode is Normal wildcard search

#### 1.1.0 / 2020/09/17

Fixed: Added $wpdb->prepare() to all where statements
Added: Added code to support premium version  

#### 1.0.1 / 2020/02/19

Fixed: Revised treatment of tokens to unescape the single quote, like O'Brian

#### 1.0.0 / 2020/02/05

Initial release
