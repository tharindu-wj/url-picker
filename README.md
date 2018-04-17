# url-picker
Unyson framework url picker option type.

###Add this in hooks.php file in option types

function _action_include_link_option_type() {
    require_once dirname(__FILE__) .'/option-types/url-picker/class-fw-option-type-url-picker.php';
}
add_action('fw_init', '_action_include_link_option_type');

