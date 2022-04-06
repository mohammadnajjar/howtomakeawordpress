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
define( 'DB_NAME', 'howtomakeawordpress' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

if ( !defined('WP_CLI') ) {
    define( 'WP_SITEURL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
    define( 'WP_HOME',    $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] );
}



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
define( 'AUTH_KEY',         'X7djTJGCXkNZF2HxuWooo89P71hnUiGFvaCBNnAK9NgtF8X2IaQsiJ0iqQ5asoG7' );
define( 'SECURE_AUTH_KEY',  '1e9TQCoeg1BYlgLYsraKFLK2jmW8oquDcRWc7FQ4CKNJ7J40G710gI4FMiEGWVgm' );
define( 'LOGGED_IN_KEY',    'K4DLO2GJ37t1ijw0VSpqtFtFRlN6mwCkMHdEsza0498gmHo6gyG6855vHq2hyeYk' );
define( 'NONCE_KEY',        'ffp5AfezoYdg7ComM4e88Aut4eopH1qe3ERXEXWhuFgehxJ4lcadGwzwD6rzGKAh' );
define( 'AUTH_SALT',        'HBPjYQVbGayV9H3VbQvsBCVp6ZeaAhypT8jKspwiUPTDNCbUuZ2SkCoalke48xPB' );
define( 'SECURE_AUTH_SALT', 'eDWH0Ijd7GGMHe9KvqSxoqw5s1C5nX23Az0A4JuylIza9eoZwu0uNqAwxqh9VMPb' );
define( 'LOGGED_IN_SALT',   '1iMDvMrWVsXpqv8zpgFzlcPza9gBNgPszTDxOz1LNsphOXB33MuTz0iIGZgweNVM' );
define( 'NONCE_SALT',       'QBjIG8g6Rx2dHdyCeVAlAJkMcwsj8YYlnbwwlhUefVixjdzkrL7g0BmCkpvvUhNE' );

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
