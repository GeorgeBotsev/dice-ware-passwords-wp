<?php
/*
Plugin Name: Diceware passwords
Description: Use Diceware method for generating passwords in WordPress.
Version: 1.0
Author: George Botsev
Author URI:  https://www.george.botsev.it
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: diceware
Domain Path: /languages
*/


add_filter('random_password', 'dice_ware_password');


register_activation_hook(__FILE__, 'download_and_process_wordlist');

function dice_ware_password($password) {
    $uploads_dir = wp_upload_dir();
    $wordlist_path = $uploads_dir['basedir'] . '/diceware-wordlist.txt';
    if (!is_readable($wordlist_path)){
        download_and_process_wordlist();
    }
	$dice_ware_password = generate_dice_ware_password();
    return $dice_ware_password;
}

function download_and_process_wordlist() {
    $url = 'https://theworld.com/~reinhold/diceware.wordlist.asc';
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return;
    }

    $wordlist = wp_remote_retrieve_body($response);
    
    $start = strpos($wordlist, "\n\n") + 2;
    $end = strpos($wordlist, "\n-----BEGIN PGP SIGNATURE-----");
    $stripped_wordlist = trim(substr($wordlist, $start, $end - $start));
    
    $uploads_dir = wp_upload_dir();
    $wordlist_path = $uploads_dir['basedir'] . '/diceware-wordlist.txt';

    file_put_contents($wordlist_path, $stripped_wordlist);
}

function get_dice_ware_wordlist() {
    $uploads_dir = wp_upload_dir();
    $wordlist_path = $uploads_dir['basedir'] . '/diceware-wordlist.txt';
    
    $wordlist_contents = file_get_contents($wordlist_path);
    $lines = explode("\n", $wordlist_contents);

    $dice_ware_wordlist = [];
    foreach ($lines as $line) {
        list($key, $word) = explode("\t", $line);
        $dice_ware_wordlist[$key] = $word;
    }

    return $dice_ware_wordlist;
}

add_action('admin_menu', 'dice_ware_menu');

function dice_ware_menu() {
    add_users_page(
        'Diceware Settings', 
        'Diceware Passwords', 
        'manage_options', 
        'dice-ware-settings', 
        'dice_ware_settings_page'
    );
}

function dice_ware_settings_page() {
    ?>
    <div class="wrap">
        <h2>Diceware Password Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('dice-ware-settings-group'); ?>
            <?php do_settings_sections('dice-ware-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                <th scope="row">Number of Dice Rolls</th>
                <td><input type="number" name="dice_ware_rolls" value="<?php echo esc_attr(get_option('dice_ware_rolls', 6)); ?>" /></td>
                <td>Specify the number of dice rolls that are to be performed in order to get a password of that lenght</td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'dice_ware_settings');

function dice_ware_settings() {
    register_setting('dice-ware-settings-group', 'dice_ware_rolls');
    
    add_settings_section(
        'dice-ware-main-section',
        'Main Settings',
        'dice_ware_main_section_callback',
        'dice-ware-settings'
    );
    
    add_settings_field(
        'dice_ware_rolls',
        'Number of Dice Rolls',
        'dice_ware_rolls_callback',
        'dice-ware-settings',
        'dice-ware-main-section'
    );
}

function dice_ware_main_section_callback() {
    echo 'Configure Diceware settings below:';
}

function dice_ware_rolls_callback() {
    $value = esc_attr(get_option('dice_ware_rolls', 6));
    echo "<input type='number' name='dice_ware_rolls' value='$value' />";
}

function generate_dice_ware_password() {
    $dice_ware_wordlist = get_dice_ware_wordlist();
    
    $word_count = get_option('dice_ware_rolls', 6);

    $password = [];
    for ($i = 0; $i < $word_count; $i++) {
        $roll_result = "";
        for ($j = 0; $j < 5; $j++) {
            $roll_result .= strval(mt_rand(1, 6));
        }
        $password[] = $dice_ware_wordlist[$roll_result];
    }
    return implode('-', $password);
}

?>
