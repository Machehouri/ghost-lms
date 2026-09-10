<?php
/**
 * Course-to-product linking meta box.
 *
 * @package GhostLMS\Admin
 */

declare(strict_types=1);

namespace GhostLMS\Admin;

use WC_Product;
use WC_Product_Simple;
use WP_Post;

final class ProductLinkMetaBox
{
    public function register(): void
    {
        add_action('add_meta_boxes_glms_course', [$this, 'register_meta_box']);
        add_action('save_post_glms_course', [$this, 'save_course_product_link'], 10, 2);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_meta_box(): void
    {
        add_meta_box(
            'ghost_lms_course_product_link',
            __('Course Product Link', 'ghost-lms'),
            [$this, 'render_meta_box'],
            'glms_course',
            'normal',
            'default'
        );
    }

    public function render_meta_box(WP_Post $post): void
    {
        wp_nonce_field('ghost_lms_save_course_product_link', 'ghost_lms_course_product_link_nonce');

        $course_id = (int) $post->ID;
        $product_id = absint(get_post_meta($course_id, '_glms_woo_product_id', true));
        $product = $product_id > 0 ? wc_get_product($product_id) : false;

        echo '<div class="glms-course-product-link">';

        if ($product instanceof WC_Product) {
            echo '<p><strong>' . esc_html__('Currently linked product', 'ghost-lms') . ':</strong> ' . esc_html($product->get_name()) . '</p>';
        } else {
            echo '<p>' . esc_html__('No WooCommerce product is linked to this course yet.', 'ghost-lms') . '</p>';
        }

        echo '<div style="margin-top: 16px;">';
        echo '<p><strong>' . esc_html__('Link an existing simple product', 'ghost-lms') . '</strong></p>';
        echo '<input type="text" id="ghost-lms-product-search" class="regular-text" placeholder="' . esc_attr__('Search WooCommerce products…', 'ghost-lms') . '" style="width: 100%; margin-bottom: 8px;" />';
        echo '<select id="ghost-lms-product-results" name="ghost_lms_existing_product_id" style="width: 100%; min-height: 42px;">';
        echo '<option value="">' . esc_html__('Select a product', 'ghost-lms') . '</option>';

        if ($product instanceof WC_Product) {
            echo '<option value="' . esc_attr((string) $product_id) . '" selected>' . esc_html($product->get_name()) . '</option>';
        }

        echo '</select>';
        echo '<p class="description">' . esc_html__('Only simple products are supported for v1 course sales.', 'ghost-lms') . '</p>';
        echo '</div>';

        echo '<div style="margin-top: 20px;">';
        echo '<p><strong>' . esc_html__('Create a new simple product', 'ghost-lms') . '</strong></p>';
        echo '<label for="ghost_lms_new_product_name" style="display:block; margin-bottom: 8px;"><span class="screen-reader-text">' . esc_html__('Product name', 'ghost-lms') . '</span></label>';
        echo '<input type="text" id="ghost_lms_new_product_name" name="ghost_lms_new_product_name" value="' . esc_attr($post->post_title) . '" class="regular-text" style="width: 100%; margin-bottom: 8px;" />';
        echo '<label for="ghost_lms_new_product_price" style="display:block; margin-bottom: 8px;"><span class="screen-reader-text">' . esc_html__('Product price', 'ghost-lms') . '</span></label>';
        echo '<input type="number" id="ghost_lms_new_product_price" name="ghost_lms_new_product_price" step="0.01" min="0" value="" class="regular-text" style="width: 100%;" placeholder="39.00" />';
        echo '</div>';

        echo '<div style="margin-top: 20px;">';
        echo '<button type="submit" name="ghost_lms_link_existing_product" value="1" class="button button-primary">' . esc_html__('Link selected product', 'ghost-lms') . '</button> ';
        echo '<button type="submit" name="ghost_lms_create_new_product" value="1" class="button">' . esc_html__('Create and link product', 'ghost-lms') . '</button> ';
        echo '<button type="submit" name="ghost_lms_unlink_product" value="1" class="button button-link-delete" style="color: #b32d2e;">' . esc_html__('Unlink product', 'ghost-lms') . '</button>';
        echo '</div>';

        echo '<script>
            (function(){
                var searchInput = document.getElementById("ghost-lms-product-search");
                var resultsSelect = document.getElementById("ghost-lms-product-results");
                if (!searchInput || !resultsSelect) {
                    return;
                }

                searchInput.addEventListener("input", function() {
                    var term = searchInput.value.trim();
                    if (term.length < 2) {
                        return;
                    }

                    fetch(ajaxurl + "?action=woocommerce_json_search_products&term=" + encodeURIComponent(term), {
                        method: "GET",
                        headers: {
                            "Accept": "application/json"
                        }
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(items) {
                        if (!Array.isArray(items)) {
                            return;
                        }

                        resultsSelect.innerHTML = "<option value=\"\">Select a product</option>";
                        items.forEach(function(item) {
                            var option = document.createElement("option");
                            option.value = String(item.id || item.ID || "");
                            option.textContent = item.text || item.name || "Product";
                            resultsSelect.appendChild(option);
                        });
                    })
                    .catch(function() {
                        resultsSelect.innerHTML = "<option value=\"\">Select a product</option>";
                    });
                });
            })();
        </script>';

        echo '</div>';
    }

    public function save_course_product_link(int $post_id, WP_Post $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (! current_user_can('edit_post', $post_id)) {
            return;
        }

        if (! isset($_POST['ghost_lms_course_product_link_nonce']) || ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ghost_lms_course_product_link_nonce'])), 'ghost_lms_save_course_product_link')) {
            return;
        }

        if (isset($_POST['ghost_lms_unlink_product'])) {
            $this->unlink_course_product($post_id);
            return;
        }

        $product_id = 0;

        if (isset($_POST['ghost_lms_link_existing_product']) || isset($_POST['ghost_lms_existing_product_id'])) {
            $product_id = absint(wp_unslash($_POST['ghost_lms_existing_product_id'] ?? 0));
            if ($product_id > 0) {
                $this->link_course_product($post_id, $product_id, false);
            }
            return;
        }

        if (isset($_POST['ghost_lms_create_new_product'])) {
            $product_name = isset($_POST['ghost_lms_new_product_name']) ? sanitize_text_field(wp_unslash($_POST['ghost_lms_new_product_name'])) : '';
            $price = isset($_POST['ghost_lms_new_product_price']) ? (float) wp_unslash($_POST['ghost_lms_new_product_price']) : 0.0;

            if ('' !== $product_name) {
                $product_id = $this->create_and_link_product($post_id, $product_name, $price);
            }
        }
    }

    public function enqueue_assets(string $hook): void
    {
        $screen = get_current_screen();

        if (! $screen || 'glms_course' !== $screen->post_type) {
            return;
        }

        if (! in_array($hook, ['post.php', 'post-new.php'], true)) {
            return;
        }

        wp_enqueue_script('jquery');
    }

    private function link_course_product(int $course_id, int $product_id, bool $is_new_product): bool
    {
        if ($product_id <= 0) {
            return false;
        }

        $product = wc_get_product($product_id);
        if (! $product instanceof WC_Product) {
            return false;
        }

        if ('simple' !== $product->get_type()) {
            add_settings_error('ghost_lms_product_link', 'ghost_lms_product_type', __('Only simple products can be linked to a course.', 'ghost-lms'), 'error');
            return false;
        }

        $existing_course_id = absint(get_post_meta($product_id, '_glms_course_id', true));
        if ($existing_course_id > 0 && $existing_course_id !== $course_id) {
            add_settings_error('ghost_lms_product_link', 'ghost_lms_product_collision', __('This product is already linked to a different course.', 'ghost-lms'), 'error');
            return false;
        }

        update_post_meta($course_id, '_glms_woo_product_id', $product_id);
        update_post_meta($product_id, '_glms_course_id', $course_id);

        if ($is_new_product) {
            add_settings_error('ghost_lms_product_link', 'ghost_lms_product_linked', __('New WooCommerce product created and linked to this course.', 'ghost-lms'), 'success');
        }

        return true;
    }

    private function create_and_link_product(int $course_id, string $product_name, float $price): int
    {
        if (! class_exists('WC_Product_Simple')) {
            return 0;
        }

        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_regular_price((string) $price);
        $product->set_virtual(true);
        $product->set_status('publish');
        $product->save();

        $product_id = $product->get_id();

        if ($product_id > 0) {
            $this->link_course_product($course_id, $product_id, true);
        }

        return $product_id;
    }

    private function unlink_course_product(int $course_id): void
    {
        $product_id = absint(get_post_meta($course_id, '_glms_woo_product_id', true));

        if ($product_id > 0) {
            delete_post_meta($product_id, '_glms_course_id');
        }

        delete_post_meta($course_id, '_glms_woo_product_id');
    }
}
