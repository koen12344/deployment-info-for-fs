<?php
/**
 * Plugin Name:     Deployment info for Freemius
 * Plugin URI:      https://digitaldistortion.dev
 * Description:     Increase customer confidence by displaying info about your most recent plugin update
 * Author:          Koen Reus
 * Author URI:      https://koenreus.com
 * Text Domain:     deployment-info-for-fs
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Deployment_Info_For_Fs
 */


use Koen12344\DeploymentInfoForFs\Plugin;

require __DIR__.'/vendor/autoload.php';

register_activation_hook(__FILE__, ['\Koen12344\DeploymentInfoForFs\Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['\Koen12344\DeploymentInfoForFs\Plugin', 'deactivate']);

$deployment_info_for_fs = new Plugin(__FILE__);

add_action('after_setup_theme', [$deployment_info_for_fs, 'load_plugin']);
