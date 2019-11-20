<?php
/**
 * WordPress の基本設定
 *
 * このファイルは、MySQL、テーブル接頭辞、秘密鍵、ABSPATH の設定を含みます。
 * より詳しい情報は {@link http://wpdocs.sourceforge.jp/wp-config.php_%E3%81%AE%E7%B7%A8%E9%9B%86 
 * wp-config.php の編集} を参照してください。MySQL の設定情報はホスティング先より入手できます。
 *
 * このファイルはインストール時に wp-config.php 作成ウィザードが利用します。
 * ウィザードを介さず、このファイルを "wp-config.php" という名前でコピーして直接編集し値を
 * 入力してもかまいません。
 *
 * @package WordPress
 */

// 注意: 
// Windows の "メモ帳" でこのファイルを編集しないでください !
// 問題なく使えるテキストエディタ
// (http://wpdocs.sourceforge.jp/Codex:%E8%AB%87%E8%A9%B1%E5%AE%A4 参照)
// を使用し、必ず UTF-8 の BOM なし (UTF-8N) で保存してください。

// ** MySQL 設定 - この情報はホスティング先から入手してください。 ** //
/** WordPress のためのデータベース名 */
define( 'DB_NAME', 'life' );

/** MySQL データベースのユーザー名 */
define( 'DB_USER', 'haokexin' );

/** MySQL データベースのパスワード */
define( 'DB_PASSWORD', 'danding201' );

/** MySQL のホスト名 */
define( 'DB_HOST', 'localhost' );

/** データベースのテーブルを作成する際のデータベースの文字セット */
define( 'DB_CHARSET', 'utf8mb4' );

/** データベースの照合順序 (ほとんどの場合変更する必要はありません) */
define('DB_COLLATE', '');

/**#@+
 * 認証用ユニークキー
 *
 * それぞれを異なるユニーク (一意) な文字列に変更してください。
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org の秘密鍵サービス} で自動生成することもできます。
 * 後でいつでも変更して、既存のすべての cookie を無効にできます。これにより、すべてのユーザーを強制的に再ログインさせることになります。
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'w$0x!j-T5 $v=/i5ee%xD8i/#;[1tvtd]SC&@ SyA{I*Rn`gyG3HS1fQVYjYKaP}' );
define( 'SECURE_AUTH_KEY',  '4[Cf6=wl3p%Ed@UpC[pX6ZikdlEqEt<aHX19ZkfWztp~@MP6DMZExgPXdk[?8%Mn' );
define( 'LOGGED_IN_KEY',    '0z?rO52H`!$K*DZEsf3z`Jr~c>Phdy{s=D.>lY#qRCHqo*4+S+!z!p|FcZQ{T}wG' );
define( 'NONCE_KEY',        'Mu~:wx8F9E_J<NUl0m.hv[uE~fLM>JAt4ZQ6SBgAA&i%J.k]Znl6g!YzPGtF.Cym' );
define( 'AUTH_SALT',        '^[a?65Qm.4HIk+[|ResX#)eYv6VxQJdOF<fo+`BS>Xn:mCF:^Fne03g6G)cJ$.!q' );
define( 'SECURE_AUTH_SALT', '2lX&H(Sf9$P@kqC3,@ZaGkIwa@%^x35nC~XT`)o2lGe&4k5h1S.xiAKfHE~Y7%0l' );
define( 'LOGGED_IN_SALT',   'T5)B5weFdod_mn>*sm8ZS/M>y4t1W H2^8[Og41>/4ofV/;&7nwyr6.,T[SzEon;' );
define( 'NONCE_SALT',       '&=#UrGtRzx%0=KSUugrsRb}-P`h[gqd>T$2ZqgyEf$kXxX.ee3Uw,tHLvCj,K*)H' );

/**#@-*/

/**
 * WordPress データベーステーブルの接頭辞
 *
 * それぞれにユニーク (一意) な接頭辞を与えることで一つのデータベースに複数の WordPress を
 * インストールすることができます。半角英数字と下線のみを使用してください。
 */
$table_prefix  = 'hkx_';

/**
 * 開発者へ: WordPress デバッグモード
 *
 * この値を true にすると、開発中に注意 (notice) を表示します。
 * テーマおよびプラグインの開発者には、その開発環境においてこの WP_DEBUG を使用することを強く推奨します。
 */
define('WP_DEBUG', false);
define('WPLANG','zh_CN');

/*--------------------------------- 
          Turn off auto save
---------------------------------*/
define('AUTOSAVE_INTERVAL', false ); 
define('WP_POST_REVISIONS', false ); 

#define('WP_ALLOW_MULTISITE', true);
define('FORCE_SSL_ADMIN', true);
#define('WP_CACHE', true);

define('FS_METHOD', 'ftpsockets');
define('FTP_HOST', 'localhost');
define('FTP_USER', 'kusanagi');
define('FTP_PASS', 'danding201');

/* 編集が必要なのはここまでです ! WordPress でブログをお楽しみください。 */

/** Absolute path to the WordPress directory. */
if ( !defined('ABSPATH') )
	define('ABSPATH', dirname(__FILE__) . '/');

/** Sets up WordPress vars and included files. */
require_once(ABSPATH . 'wp-settings.php');
