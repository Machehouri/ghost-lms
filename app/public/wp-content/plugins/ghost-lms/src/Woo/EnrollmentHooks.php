<?php
/**
 * WooCommerce enrollment lifecycle hooks.
 *
 * @package GhostLMS\Woo
 */

declare(strict_types=1);

namespace GhostLMS\Woo;

use GhostLMS\Database\EnrollmentRepository;
use WC_Order;
use WC_Order_Item_Product;

final class EnrollmentHooks
{
    public function register(): void
    {
        add_action('woocommerce_order_status_completed', [$this, 'on_order_completed'], 10, 1);
        add_action('woocommerce_order_status_refunded', [$this, 'on_order_refunded_or_cancelled'], 10, 1);
        add_action('woocommerce_order_status_cancelled', [$this, 'on_order_refunded_or_cancelled'], 10, 1);
    }

    public function on_order_completed(int $order_id): void
    {
        $order = wc_get_order($order_id);

        if (! $order instanceof WC_Order) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        $created_any = false;

        foreach ($order->get_items() as $item) {
            if (! $item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product_id = (int) $item->get_product_id();
            $course_id = (int) get_post_meta($product_id, '_glms_course_id', true);

            if ($course_id <= 0) {
                continue;
            }

            if ($user_id <= 0) {
                $order->add_order_note(
                    sprintf(
                        /* translators: %1$d: WooCommerce order id, %2$d: product id */
                        __('Ghost LMS could not create an enrollment for order #%1$d because product #%2$d is linked to a course but the customer has no WordPress account.', 'ghost-lms'),
                        $order_id,
                        $product_id
                    )
                );
                continue;
            }

            $already_enrolled = EnrollmentRepository::is_enrolled($user_id, $course_id);
            if ($already_enrolled) {
                EnrollmentRepository::create_or_reactivate($user_id, $course_id, $order_id);
                continue;
            }

            EnrollmentRepository::create_or_reactivate($user_id, $course_id, $order_id);
            do_action('ghost_lms_enrollment_created', $user_id, $course_id, $order_id);
            $created_any = true;
        }

        if ($created_any && $user_id <= 0) {
            $order->add_order_note(
                __('Ghost LMS could not create an enrollment for a guest checkout purchase because the order is not linked to a WordPress user account.', 'ghost-lms')
            );
        }
    }

    public function on_order_refunded_or_cancelled(int $order_id): void
    {
        EnrollmentRepository::revoke_by_order($order_id);
    }
}
