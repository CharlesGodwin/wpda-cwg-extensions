<?php
class WPDA_XS_Admin
{

    public function __construct()
    {
        add_option('wpda_xs_disable', false);
        add_option('wpda_xs_skiptables', '');
        // create custom plugin settings menu
        add_action('admin_menu', [$this, 'wpda_xs_create_menu']);
    }
    public function wpda_xs_create_menu()
    {

        //create new top-level menu
        add_options_page(
            'WP Data Access XSearch Plugin',
            'WPDA XSearch',
            'administrator',
            'wpda_xs_options',
            [$this, 'wpda_xs_settings_page']);

        // add_menu_page('WP Data Access XSearch Plugin Settings', 'WP Data Access XSearch Settings', 'administrator', __FILE__, '_settings_page', plugins_url());

        //call register settings function
        add_action('admin_init', [$this, 'wpda_xs_register_settings']);
    }

    public function wpda_xs_register_settings()
    {
        //register our settings
        register_setting('wpda-xs-settings-group', 'wpda_xs_disable', array('type' => 'boolean', 'default' => false));
        register_setting('wpda-xs-settings-group', 'wpda_xs_skiptables');
    }

    public function wpda_xs_settings_page()
    {
        ?>
<div class="wrap">
<h1>WPDA XS Extended Search
<a href="https://github.com/CharlesGodwin/wpda-cwg-extensions" target="_blank" title="Extended Search Documentation">Help</a>
</h1>

<form method="post" action="options.php">
    <?php settings_fields('wpda-xs-settings-group');
        $option = get_option('wpda_xs_disable');?>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Disable plug-in</th>
        <td><input type="checkbox" name="wpda_xs_disable" value="1" <?php echo $option == '1' ? "checked" : ""; ?> />
        Disable the plug-in temporarily for testing / debugging purposes</td>
        </tr>
        <tr valign="top">
        <th scope="row">Skip these tables</th>
        <td><input type="text" name="wpda_xs_skiptables" value="<?php echo get_option('wpda_xs_skiptables'); ?>" />
        Do not use plugin for these tables: comma seperated list of schema.table pairs.</td>
        </tr>
    </table>
<p>This plug-in adds an extended search capability to WP Data Access plug-in. It supports searching for each token in a string instead
of the default of searching for the entire string.</p>
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes');?>" />
    </p>

</form>
</div>
<?php }
}?>
