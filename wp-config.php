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
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ppt' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

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
define( 'AUTH_KEY',         'TtzLj5=p`X}?X=YfMDh&_QD`jblI+%QqPj6X&s@[0Y&u4>Mq8XTI%RA6V:mtU}p~' );
define( 'SECURE_AUTH_KEY',  '&S)$O[VO<6*am|,  =d%j>jl,s(HmWt9[c?K,> fv46^Z9tVTPGqxP3z|FIKjDPT' );
define( 'LOGGED_IN_KEY',    '0FRwA,c]*o!(ZB3jt9NNz &[.6-GMg1VIy?*`4gx9V3!4tO;itAi1z^{H~/!k%0t' );
define( 'NONCE_KEY',        '}rhcWgGyD+fZSKM$QxLfg}~q+B$]-;%3%KXLZ<.Wt5uKXvb95Qi9u&f^{%=k0]-^' );
define( 'AUTH_SALT',        'P^dI/.Ag4zL]uGn+A5tbr2]X<([:dEn2YEGWN<+%]H|RmGQp=UW4E{2l|@iYc9Y%' );
define( 'SECURE_AUTH_SALT', 'Y(U>=hIH0DXcDz1k~g1Gg4/{ux1[$}<K_^d8$x4n@21vCst)HMu,);zWE,CDDZq%' );
define( 'LOGGED_IN_SALT',   'e4r37ltF@&(V&gKKnBhHTJ!o|G0>N^zsa38!N/mk|pyh*%1^.2]<UewzvLVN0:h0' );
define( 'NONCE_SALT',       '|&S)jhHerh1s$ iqI/)G;7<o_O;p.Lc{vB@ET!ZS]t*9Ys511mA0w$jai<MVS`~&' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
