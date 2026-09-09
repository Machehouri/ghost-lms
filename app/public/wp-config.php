<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '$FR(jsShic MB.sm?>w#_RX`k{`~.=hy6[1RhHRM4x{r;8qQ J3mB&/W.J?m-6D7' );
define( 'SECURE_AUTH_KEY',   '*:$vGXoS,uBI/hXb7;<xrqk.`ak~KgT*2_H4.>K%g+P]9r+dD(zk~tQ@--l9+Wg>' );
define( 'LOGGED_IN_KEY',     'oX2@y,?s@@;(YU7r-b%bSw@1oL~T6=K%C<>Du uz?v5rCT$vOL!bnrgtC)+OoTrA' );
define( 'NONCE_KEY',         '<](/@D0.:l~oFK,O1%?(~+o4J|Q!{90p-sM3.-WPF*{5ejS+d]vkH~*7K32g4!W+' );
define( 'AUTH_SALT',         'KN54Y7[`Nd<9VOJ^=LCi;8edf&G.OB2-*6W0_3kLPFdo.,nTaa[GZ`B7m^Q,C1=+' );
define( 'SECURE_AUTH_SALT',  'eo>zBRbYlG1Y)0ppjL)@GE6A`!Y[,_t`hbr(]4_VdRR+Z045``#1M05lQ:eFAvy5' );
define( 'LOGGED_IN_SALT',    'yR6unkvQ2F~:7tOkGN@awk>2&3L>TWW9tZ!**w>kf8RO[iUIv^{-cp3)f!i ^Hi<' );
define( 'NONCE_SALT',        '@DIB7P#*J(%5Zn3(IK(W9jS|UAT/kL&|WQ`47hLlVS}&8_qc}WzD4`n.79+lu9EL' );
define( 'WP_CACHE_KEY_SALT', 'ZU]<e7B<AHf38^gm+8]Z~(zgf|>q^1!ao&(K:{Nh5Lz)Z~WOm]{Ax$hnD;14k3.S' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
