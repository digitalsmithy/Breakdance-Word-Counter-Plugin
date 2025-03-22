<?php
/*
Plugin Name: Breakdance Reading Time Calculator
Description: Calculate reading time for Breakdance builder content and store it as post meta.
Version: 1.0
Author: Digital Smithy
*/

// Add settings menu
add_action('admin_menu', 'bd_reading_time_menu');
function bd_reading_time_menu() {
    add_menu_page('Breakdance Reading Time', 'Breakdance Reading Time', 'manage_options', 'bd-reading-time', 'bd_reading_time_settings_page');
}

// Register settings
add_action('admin_init', 'bd_reading_time_register_settings');
function bd_reading_time_register_settings() {
    register_setting('bd_reading_time_settings_group', 'bd_words_per_minute', array('default' => 238));
}

// Settings page content
function bd_reading_time_settings_page() {
    ?>
    <div class="wrap">
        <h1>Breakdance Reading Time Calculator</h1>
        <form method="post" action="options.php">
            <?php settings_fields('bd_reading_time_settings_group'); ?>
            <?php do_settings_sections('bd_reading_time_settings_group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Words per Minute</th>
                    <td><input type="number" name="bd_words_per_minute" value="<?php echo esc_attr(get_option('bd_words_per_minute', 238)); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Post ID</th>
                    <td><input type="number" id="bd_post_id" name="bd_post_id" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Process All Posts</th>
                    <td>
                        <?php bd_get_post_types(); ?>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
            <button type="button" id="bd_start_process" class="button button-primary">Start</button>
        </form>
    </div>
    <script type="text/javascript">
        document.getElementById('bd_start_process').addEventListener('click', function() {
            var postId = document.getElementById('bd_post_id').value;
            var postTypes = Array.from(document.querySelectorAll('input[name="bd_post_types[]"]:checked')).map(cb => cb.value);
            var data = {
                action: 'bd_calculate_reading_time',
                post_id: postId,
                post_types: postTypes,
            };
            jQuery.post(ajaxurl, data, function(response) {
                alert(response);
            });
        });
    </script>
    <?php
}

// Get registered post types
function bd_get_post_types() {
    $post_types = get_post_types(array('public' => true), 'objects');
    foreach ($post_types as $post_type) {
        echo '<label><input type="checkbox" name="bd_post_types[]" value="' . esc_attr($post_type->name) . '"> ' . esc_html($post_type->label) . '</label><br>';
    }
}

// Calculate reading time
add_action('wp_ajax_bd_calculate_reading_time', 'bd_calculate_reading_time');
function bd_calculate_reading_time() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized user');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_types = isset($_POST['post_types']) ? $_POST['post_types'] : array();
    $words_per_minute = get_option('bd_words_per_minute', 238);

    if ($post_id) {
        bd_process_post($post_id, $words_per_minute);
    } else {
        $args = array(
            'post_type' => $post_types,
            'meta_key' => '_breakdance_data',
            'posts_per_page' => -1,
        );
        $query = new WP_Query($args);
        while ($query->have_posts()) {
            $query->the_post();
            bd_process_post(get_the_ID(), $words_per_minute);
        }
        wp_reset_postdata();
    }

    wp_die('Reading time calculation complete.');
}

function bd_process_post($post_id, $words_per_minute) {
    $meta = get_post_meta($post_id, '_breakdance_data', true);
    if (!$meta) return;

    $data = json_decode($meta, true);

    // Decode the "tree_json_string"
    if (isset($data['tree_json_string'])) {
        $tree_data = json_decode($data['tree_json_string'], true);
    } else {
        $tree_data = null; // Or handle the case where 'tree_json_string' doesn't exist
    }

    //If no tree data, return.
    if (is_null($tree_data)){
        return;
    }

    $word_count = bd_count_words($tree_data);
    $reading_time = ceil($word_count / $words_per_minute);

    update_post_meta($post_id, '_bd_word_count', $word_count);
    update_post_meta($post_id, '_bd_read_time', $reading_time);
}

function bd_count_words($data) {
    $word_count = 0;
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $word_count += bd_count_words($value);
        } elseif ($key === 'text') {
            $word_count += str_word_count(strip_tags($value));
        }
    }
    return $word_count;
}
?>
