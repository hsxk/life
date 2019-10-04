<?php

class wexal_pst_worker {

public $args;
public $pst;
public $tdir;
public $odir;
public $exclude;
public $force = false;
public $extreg = '(gif|png|jpg|jpeg|js|css)';
public $img_ext = array( 'png', 'jpg', 'gif', 'jpeg' );
public $conf;
public $method;
public $queue;
public $backend;

function __construct( $pst, $args=array() ) {
	if ( ! is_array( $args ) ) {
		$args = array( $args );
	}
	$ret = shell_exec( 'echo $PATH' );
	if ( ! preg_match( '#java#', $ret ) ) {
		$ret = shell_exec( 'export JAVA_HOME=/usr/lib/jvm/jre-1.8.0-openjdk' );
		$ret = shell_exec( 'export PATH=$PATH:$JAVA_HOME/bin' );
	}
	
	$method = array_shift( $args );
	$this->args = $args;

	$this->pst = $pst;
	$this->tdir = $this->pst->DocumentRoot;
	$this->odir = $this->pst->wexal_dir . '/optdir';
	$this->exclude = '#(' . join( '|', $this->pst->config['global_exclude']) . ')#';

	if ( isset( $pst->config['timezone'] ) ) {
		date_default_timezone_set( $pst->config['timezone'] );
	}

	if ( ! isset( $pst->config['worker'] ) || ! is_array( $pst->config['worker'] ) ) { return false; };

	$w = $pst->config['worker'];

	// compat
	if ( isset( $w['img'][''][0] ) && isset( $w['img'][''][0]['resize'] ) ) {
		array_shift( $w['img'][''] );
	}

	// cmd
	function cmd_merge( $def, $conf ) {
		foreach ( $def as $key => $val ) {
			if ( ! isset( $conf[ $key ] ) || ! is_array( $conf[ $key ] ) ) {
				$conf[ $key ] = false;
			}
			if ( is_array( $conf[ $key ] ) && false == $conf[ $key ] ) {
				$conf[ $key ] = $val;
			}
		}
		return $conf;
	}

	$def = array(
		'img' => array(),
		'css' => array( array( 'cmd' => 'auto' ) ),
		'js'  => array( array( 'cmd' => 'auto' ) )
	);
	$w = cmd_merge( $def, $w );
	
	$def = array(
		''      => array( array( 'cmd' => 'auto' ) ),
		'.webp' => array( array( 'cmd' => 'auto' ) ),
		'.jp2'  => array( array( 'cmd' => 'auto' ) ),
		'.jxr'  => array( array( 'cmd' => 'auto' ) )
	);
	$w['img'] = cmd_merge( $def, $w['img'] );

	// preset
	function preset_merge( $def, $type, $conf ) {
		if ( ! is_array( $conf ) ) { return $conf; }
		foreach ( $conf as $idx => $row ) {
			if ( ! is_array( $row ) ) { continue; }
			if ( isset( $row['cmd'] ) && in_array( $row['cmd'], array_keys( $def[ $type ] ) ) ) {
				if ( isset( $row['args'] ) && is_array( $row['args'] ) ) {
					$row['args'] = array_merge( $def[ $type ][ $row['cmd'] ], $row['args'] );
				} else {
					$row['args'] = $def[ $type ][ $row['cmd'] ];
				}
				if ( isset( $row['apply'] ) ) {
					if ( ! is_array( $row['apply'] ) ) {
						$row['apply'] = array( $row['apply'] );
					}
				} else {
					$row['apply'] = array( '.' );
				}
				if ( isset( $row['exclude'] ) ) {
					if ( ! is_array( $row['exclude'] ) ) {
						$row['exclude'] = array( $row['exclude'] );
					}
				} else {
					$row['exclude'] = array( '' );
				}
			}
			$conf[ $idx ] = $row;
		}
		return $conf;
	}

	$def = array(
		'' => array(
			'auto' => array(
				'quality'  => 60,
				'resize'  => '1920x1080',
				'strip'    => true,
				'jpegtran' => 'ab',
				'pngquant' => true,
				'optipng'  => 'o3',
			),
			'fast' => array(
				'quality'  => 60,
				'resize'  => '1920x1080',
				'strip'    => true,
				'jpegtran' => false,
				'pngquant' => true,
				'optipng'  => false,
			),
		),
		'.webp' => array(
			'auto' => array(
				'quality'  => 60,
				'm'        => 6,
				'mt'       => true,
			),
			'fast' => array(
				'quality'  => 60,
				'm'        => 4,
				'mt'       => true,
			),
		),
		'.jp2' => array(
			'auto' => array(
				'quality'  => 60,
				'numrlvls' => 16,
				'ab'       => true,
			),
			'fast' => array(
				'quality'  => 60,
				'numrlvls' => 6,
				'ab'       => false,
			),
		),
		'.jxr' => array(
			'auto' => array(
				'quality'  => 60,
				'F'        => 3,
				'l'        => 1,
				'ab'       => true,
			),
			'fast' => array(
				'quality'  => 60,
				'F'        => 0,
				'l'        => 1,
				'ab'       => false,
			),
		),
		'js' => array(
			'auto' => array(
				'compiler' => 'gcc,terser',
				'level'    => 'WHITESPACE_ONLY',
				'ab'       => true,
				'ie11'     => true,
				'ie11_cp'  => 'babel',
				'ie11_ab'  => true,
			),
			'fast' => array(
				'compiler' => 'terser',
				'level'    => 'WHITESPACE_ONLY',
				'ab'       => false,
				'ie11'     => false,
			),
		),
		'css' => array(
			'auto' => array(
				'compiler' => 'cssnano,cleancss',
				'level'    => 'WHITESPACE_ONLY',
				'ab'       => true,
				'ie11'     => true,
				'ie11_ab'  => true,
			),
			'fast' => array(
				'compiler' => 'cssnano',
				'level'    => 'WHITESPACE_ONLY',
				'ab'       => false,
				'ie11'     => false, 
			),
		),
	);
	foreach ( $w as $key => $val ) {
		if ( 'img' == $key ) {
			// level 2 cmd
			if ( ! is_array( $val ) ) { continue; }
			foreach ( $val as $img_key => $img_val ) {
				$w[ $key ][ $img_key ] = preset_merge( $def, $img_key, $img_val );
			}
		} else {
			// level 1 cmd
			$w[ $key ] = preset_merge( $def, $key, $val );
		}
	}

	// backend
	$def = array(
		'benchmark' => false,
		'debug'     => false,
		'queue'     => array(),
		'backend'   => array()
	);
	$w = array_merge( $def, $w );

	$def = array(
		'scheme' => 'tcp',
		'host' => '127.0.0.1',
		'port' => '6379'
	);
	if ( isset( $w['queue'] ) && is_array( $w['queue'] ) ) {
		$w['queue'] = array_merge( $def, $w['queue'] );
	}

	$def = array(
		'nodejs' => array(
			'scheme' => 'http',
			'host' => '127.0.0.1',
			'port' => '3000'
		)
	);
	foreach ( $def as $key => $val ) {
		if ( isset( $w['backend'][ $key ] ) && is_array( $w['backend'][ $key ] ) ) {
			$w['backend'][ $key ] = array_merge( $val, $w['backend'][ $key ] );
		}
	}
	$this->conf = $w;

	if ( $this->conf['queue'] ) {
		$scheme = $this->conf['queue']['scheme'];
		$host   = $this->conf['queue']['host'];
		$port   = $this->conf['queue']['port'];
		if ( $scheme && $host && $port ) {
			$redis = new Redis();
			$scheme = ( 'tcp' == $scheme ) ? '' : $scheme . '://';
			if ( $redis->connect( $scheme . $host, $port ) ) {
				$redis->setOption( Redis::OPT_READ_TIMEOUT, -1 );
				$this->queue = $redis;
				if ( $this->conf['debug'] ) {
					$this->log( "queue $scheme$host:$port" );
				}
			} else {
				$this->log( "Failed to connect queue $scheme://$host:$port, falling back to serialized mode." );
			}
		}
	}

	$this->backend = array(
		'nodejs' => ''
	);
	foreach ( $this->conf['backend'] as $key => $val ) {
		if ( false == $val ) { continue; }
		$scheme = $this->conf['backend'][ $key ]['scheme'];
		$host   = $this->conf['backend'][ $key ]['host'];
		$port   = $this->conf['backend'][ $key ]['port'];
		if ( $scheme && $host && $port ) {
			switch ( $key ) {
				case 'nodejs':
					$backend = "$scheme://$host:$port";
					if ( @file_get_contents( $backend ) ) {
						$this->backend[ $key ] = $backend;
						if ( $this->conf['debug'] ) {
							$this->log( "backend [$key] $backend" );
						}
					} else {
						$this->log( "Failed to connect backend $scheme://$host:$port, falling back to shell_exec mode." );
					}
					break;
			}
		}
	}

	if ( $method ) {
		$this->dispatch( $method );
	}
}

function is_apply( $apply, $exclude, $file_path ) {
	$ret = false;
	foreach ( $apply as $key => $val ) {
		$regex = "#$val#";
		if ( '' !== $val && @preg_match( $regex, $file_path ) ) {
			$ret = true;
			break;
		}
	}

	foreach ( $exclude as $key => $val ) {
		$regex = "#$val#";
		if ( '' !== $val && @preg_match( $regex, $file_path ) ) {
			$ret = false;
			break;
		}
	}
	return $ret;
}

function dispatch( $method ) {
	if ( 'optimize' == $method ) {
		$this->optimize();
	} elseif ( in_array( $method, array( 'opt_js', 'opt_css', 'opt_image', 'opt', 'uncss' ) ) ) {
		if ( 'opt_js' == $method ) { $this->extreg = '(js)'; }
		if ( 'opt_css' == $method ) { $this->extreg = '(css)'; }
		if ( 'opt_image' == $method ) { $this->extreg = '(gif|png|jpg|jpeg)'; }
		if ( 'uncss' == $method ) { $this->extreg = '(css)'; }
		
		$this->method = $method;

		if ( in_array( '--force', $this->args ) ) {
			$this->force = true;
		}

		$t = array_shift( $this->args );
		if ( $t && '--force' != $t ) {
			$t = preg_replace( '#^/+#', '', $t );
			$t = preg_replace( '#/+$#', '', $t );
			$t = $this->pst->DocumentRoot . '/' . $t;
			if ( is_file( $t ) ) {
				$this->opt_file( $t );
			} elseif ( is_dir ( $t ) ) {
				$this->optimize( $t );
			} else {
				echo "$t is not found\n";
			}
		} else {
			$this->optimize();
		}
	} elseif ( 'purge' == $method ) {
		$dir = array_shift( $this->args );
		if ( $dir ) {
			$dir = $this->odir . '/' . trim( $dir, '/' );
		} else {
			$dir = false;
		}
		$this->purge( $dir );
	}
}

function get_config_file( $filename ) {
	$pst = $this->pst->pst_dir . '/' . $filename;
	$usr = $this->pst->userdir . '/' . $filename;
	if ( is_file ( $usr ) ) {
		return $usr;
	} else {
		return $pst;
	}
}

function optimize( $target_dir=false ) {
	static $count;
	if ( false == $target_dir ) {
		$target_dir = $this->pst->config['tdir'];
	}
	$exclude = $this->exclude;

	$dir = dir( $target_dir );
	while ( $file = $dir->read() ) {
		$file_path = $target_dir . '/' . $file;
		if ( preg_match( '#^\.{1,2}$#', $file ) ) {
			continue;
		} elseif ( preg_match( $this->exclude, $file_path ) ) {
			continue;
		}
		if ( is_dir ( $file_path ) ) {
			$this->optimize( $file_path );
		}
		if ( is_file ( $file_path ) && preg_match( '/\.' . $this->extreg . '$/i', $file_path ) ) {
			$this->opt_file( $file_path );
		}
	}
}

function opt_file_wait() {
	if ( $this->queue ) {
		$ret = $this->queue->blPop( $this->pst->profile, 0 );
		if ( $ret ) {
			$job = json_decode( $ret[1], true );
			$file_path = $job['file_path'];
			$this->force = $job['force'];
			$this->method = $job['method'];
			if ( $this->conf['debug'] ) {
				$this->log( "queue [blpop] $file_path" );
			}
			$this->opt_file_exec( $file_path );
			return $file_path;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function opt_file( $file_path ) {
	if ( preg_match( $this->exclude, $file_path ) ) { return; }
	if ( ! preg_match( '/\.' . $this->extreg . '$/i', $file_path ) ) { return; }

	if ( $this->queue ) {
		$json = json_encode( array(
			'file_path' => $file_path,
			'force' => $this->force,
			'method' => $this->method
		) );
		if ( $this->conf['debug'] ) {
			$this->log( "queue [rpush] $file_path" );
		}
		$this->queue->rPush( $this->pst->profile, $json );
	} else {
		$this->opt_file_exec( $file_path );
	}
}

function opt_file_exec( $file_path ) {
	// original ext
	$ext = $this->get_pathinfo( $file_path, 'extension' );
	// optimize path ( without new extension )
	$opt_path_without_ext = $this->get_optimize_path( $file_path );
	// optimze dir
	$opt_dir = $this->get_pathinfo( $opt_path_without_ext, 'dirname' );

	$do = array();
	if ( in_array( $ext, $this->img_ext ) ) {
		if ( ! is_array( $this->conf['img'] ) ) { return; }
		$types = array_keys( $this->conf['img'] );
		foreach ( $types as $newtype ) {
			if ( ! is_array( $this->conf['img'][$newtype] ) ) { continue; }
			$opt_path = $opt_path_without_ext . $newtype;
			$ret = $this->check_time_stamp( $file_path, $opt_path );
			if ( $ret ) {
				$do[] = array( $opt_path, $file_path, $ext, $newtype, $ret );
			}
		}
	} elseif ( 'css' == $ext ) {
		if ( ! is_array( $this->conf['css'] ) ) { return; }
		$newtype = '.opt.css';
		$opt_path = $opt_path_without_ext . $newtype;
		$ret = $this->check_time_stamp( $file_path, $opt_path );
		if ( $ret ) {
			$do[] = array( $opt_path, $file_path, $ext, $newtype, $ret );
		}
	} elseif ( 'js' == $ext ) {
		if ( ! is_array( $this->conf['js'] ) ) { return; }
		$newtype = '.opt.js';
		$opt_path = $opt_path_without_ext . $newtype;
		$ret = $this->check_time_stamp( $file_path, $opt_path );
		if ( $ret ) {
			$do[] = array( $opt_path, $file_path, $ext, $newtype, $ret );
		}
	}

	if ( false == $do ) { return; }
	
	if ( ! is_dir( $opt_dir ) ) {
		mkdir( $opt_dir, 0755, true );
	}

	foreach ( $do as $cmd ) {
		if ( $this->conf['benchmark'] ) {
			$time_start = microtime( TRUE );
		}

		$this->opt_file_do( $cmd );

		if ( $this->conf['benchmark'] ) {
			$time_end = microtime( TRUE );
			$time = $time_end - $time_start;
			$this->log( "benchmark [$cmd[3]] $time $cmd[0]" );
		}
	}
}

function if_larger_than_org( $opt_org, $opt_path, $min=false ) {
	$large = $opt_path . '.large';
	if ( is_file( $opt_org ) && is_file( $opt_path ) ) {
		if ( filesize( $opt_path ) <= 0 ) {
			rename( $opt_path, $large );
			touch( $large, filemtime( $opt_org ) );
			$this->log( "[$opt_path] filesize is 0" );
		} elseif ( false == $min ) {
			if ( filesize( $opt_org ) < filesize( $opt_path ) ) {
				rename( $opt_path, $large );
				touch( $large, filemtime( $opt_org ) );
				$this->log( "[$opt_org] is smaller than [$opt_path]" );
			}
		} else {
			if ( filesize( $opt_org ) * 0.95 < filesize( $opt_path ) ) {
				rename( $opt_path, $large );
				touch( $large, filemtime( $opt_org ) );
				$this->log( "[$opt_org] => [$opt_path] is not so small" );
			}
		}
	}
}

function rename_by_ab_test( $a, $b, $opt_path ) {
	$min = false;
	$fa = '';
	$fb = '';
	if ( ! is_file( $a )) {
		$this->log( "[ab test] [empty] $a" );
		$a = '';
	} else {
		$fa = filesize( $a );
		if ( 0 === $fa ) {
			unlink( $a );
			$this->log( "[ab test] [size=0] $a" );
			$a = '';
		}
	}
	if ( ! is_file( $b )) {
		$this->log( "[ab test] [empty] $b" );
		$b = '';
	} else {
		$fb = filesize( $b );
		if ( 0 === $fb ) {
			unlink( $b );
			$this->log( "[ab test] [size=0] $b" );
			$b = '';
		}
	}
	if ( '' == $a && '' == $b ) {
		return;
	} elseif ( '' == $a ) {
		rename( $b, $opt_path );
		return;
	} elseif ( '' == $b ) {
		rename( $a, $opt_path );
		return;
	}

	if ( $fa > $fb ) {
		$min = $b;
		unlink( $a );
	} else {
		$min = $a;
		unlink( $b );
	}

	rename( $min, $opt_path );
	$this->log( "[ab test] [a:$fa, b:$fb] $min" );
}

function opt_file_do( $cmd ) {
	list( $opt_path, $file_path, $ext, $newtype, $org_time ) = $cmd;
	$in = escapeshellarg( $file_path );
	$out = escapeshellarg( $opt_path );
	$a = preg_replace( '#\.([^.]+)$#', '.a.$1', $opt_path );
	$b = preg_replace( '#\.([^.]+)$#', '.b.$1', $opt_path );
	$esc_a = escapeshellarg( $a );
	$esc_b = escapeshellarg( $b );

	$opt_org = preg_replace( '/\.(webp|jp2|jxr)$/', '', $opt_path );
	if ( in_array( $newtype, array( '.webp', '.jp2', '.jxr' ) ) && file_exists( $opt_org ) ) {
		$in = escapeshellarg( $opt_org );
	} else {
		$opt_org = $file_path;
	}

	$ret = '';
	if ( '.webp' == $newtype ) {
		$done = false;
		foreach ( $this->conf['img']['.webp'] as $key => $val ) {
			if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
				continue;
			}
			$done = true;
			$p = $val['args'];
			$quality = 0.6;
			$mt = '';
			$m = 4;
			$q = (int)$p['quality'];
			if ( $q > 0 && $q < 101 ) { $quality = $q; }
			if ( $p['mt'] ) { $mt = '-mt '; }
			$q = (int)$p['m'];
			if ( $q > -1 && $q < 7 ) { $m = $q; }

			if ( in_array( $ext, array( 'jpeg', 'jpg', 'png' ) ) ) {
				$ret .= $this->shell_exec( "cwebp -m $m $mt-q $quality $in -o $out 2>&1" );
				if ( in_array( $ext, array( 'jpeg', 'jpg' ) ) && preg_match( '/^Unsupported color conversion request/', $ret ) ) {
					$ret .= $this->shell_exec( "convert $in -verbose -colorspace RGB $out 2>&1" );
					$tmp = $this->shell_exec( "cwebp -m $m $mt-q $quality $out -o $out 2>&1" );
					if ( preg_match( '/Error\! /', $tmp ) ) {
						unlink( $opt_path );
					}
					$ret .= $tmp;
				}
				$this->if_larger_than_org( $opt_org, $opt_path );
			} elseif ( 'gif' == $ext ) {
				$ret .= $this->shell_exec( "gif2webp -m $m -lossy -q $quality $in -o $out 2>&1" );
				$this->if_larger_than_org( $opt_org, $opt_path );
			}
		} // end foreach
	} elseif ( '.jxr' == $newtype ) {
		if ( in_array( $ext, array( 'jpeg', 'jpg', 'png' ) ) ) {
			$done = false;
			foreach ( $this->conf['img']['.jxr'] as $key => $val ) {
				if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
					continue;
				}
				$done = true;
				$p = $val['args'];
				$quality = 0.6;
				$F = 3;
				$l = 1;
				$q = (int)$p['quality'];
				if ( $q > 0 && $q < 101 ) { $quality = $q / 100; }
				$q = (int)$p['F'];
				if ( $q > -1 && $q < 16 ) { $F = $q; }
				$q = (int)$p['l'];
				if ( $q > -1 && $q < 3 ) { $l = $q; }

				$tiff = escapeshellarg( $opt_path . '.tiff' );
				$ret .= $this->shell_exec( "convert $in -compress None -type truecolor -verbose $tiff 2>&1" );
				if ( $p['ab'] ) {
					$ret .= $this->shell_exec( "JxrEncApp -l 2 -F 3 -i $tiff -o $esc_a -q $quality 2>&1" );
					$ret .= $this->shell_exec( "JxrEncApp -l $l -F $F -i $tiff -o $esc_b -q $quality 2>&1" );
					$this->rename_by_ab_test( $a, $b, $opt_path );
				} else {
					$ret .= $this->shell_exec( "JxrEncApp -l $l -F $F -i $tiff -o $out -q $quality 2>&1" );
				}
				unlink( $opt_path . '.tiff' );
				$this->if_larger_than_org( $opt_org, $opt_path );
			} // end foreach
		}
	} elseif ( '.jp2' == $newtype ) {
		if ( in_array( $ext, array( 'jpeg', 'jpg', 'png' ) ) ) {
			$done = false;
			foreach ( $this->conf['img']['.jp2'] as $key => $val ) {
				if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
					continue;
				}
				$done = true;
				$p = $val['args'];
				$quality = 60;
				$numrlvls = 16;
				$q = (int)$p['quality'];
				if ( $q > 0 && $q < 101 ) { $quality = $q; }
				$q = (int)$p['numrlvls'];
				if ( $q > 0 && $q < 25 ) { $numrlvls = $q; }
			
				if ( $p['ab'] ) {
					$ret .= $this->shell_exec( "convert $in -verbose -quality $quality $esc_a 2>&1" );
					$ret .= $this->shell_exec( "convert $in -verbose -quality $quality -define jp2:numrlvls=$numrlvls $esc_b 2>&1" );
					$this->rename_by_ab_test( $a, $b, $opt_path );
				} else {
					$ret .= $this->shell_exec( "convert $in -verbose -quality $quality -define jp2:numrlvls=$numrlvls $out 2>&1" );
				}

				$this->if_larger_than_org( $opt_org, $opt_path );
			} // end foreach
		}
	} elseif ( '' == $newtype ) {
		if ( in_array( $ext, array( 'jpeg', 'jpg', 'png', 'gif' ) ) ) {
			$done = false;
			foreach ( $this->conf['img'][''] as $key => $val ) {
				if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
					continue;
				}
				$done = true;
				$p = $val['args'];
				$resize = '1920x1080>';
				$strip = '';
				$quality = 60;
				if ( preg_match( '#^\d+x\d+$#', $p['resize'] ) ) { $resize = $p['resize'] . '>'; }
				if ( $p['strip'] ) { $strip = '-strip '; }
				$q = (int)$p['quality'];
				if ( $q > 0 && $q < 101 ) { $quality = $q; }

				$ret .= $this->shell_exec( "convert $in $strip-verbose -quality $quality -resize \"$resize\" $out 2>&1" );

				if ( 'png' == $ext ) {
					if ( $p['pngquant'] ) {
						$ret .= $this->shell_exec( "pngquant --verbose --skip-if-larger --ext=.png --force $out 2>&1" );
					}
					if ( $p['optipng'] ) {
						if ( 'o7 -zm1-9' == $p['optipng'] || @preg_match( '#^o[0-7]$#', $p['optipng'] ) ) {
							$ret .= $this->shell_exec( "optipng -preserve -${p['optipng']} $out 2>&1" );
						} else {
							$ret .= $this->shell_exec( "optipng -preserve -o3 $out 2>&1" );
						}
					}
				} elseif ( 'jpeg' == $ext || 'jpg' == $ext )  { // jpeg, jpg
					if ( $strip ) {
						$strip = '-copy none';
					} else {
						$strip = '-copy all';
					}
					if ( 'ab' == $p['jpegtran'] ) {
						$ret .= $this->shell_exec( "/usr/local/bin/jpegtran -verbose -progressive $strip -optimize -outfile $esc_a $out 2>&1" );
						$ret .= $this->shell_exec( "/usr/local/bin/jpegtran -verbose -revert $strip -optimize -outfile $esc_b $out 2>&1" );
						$this->rename_by_ab_test( $a, $b, $opt_path );
					} elseif ( in_array( $p['jpegtran'], array( 'progressive', 'revert' ) ) ) {
						$ret .= $this->shell_exec( "/usr/local/bin/jpegtran -verbose -${p['jpegtran']} $strip -optimize -outfile $out $out 2>&1" );
					}
				} else { // gif

					// Coming soon.

				}
				$this->if_larger_than_org( $file_path, $opt_path );

			} // end foreach
	
		}
	} elseif ( '.opt.js' == $newtype ) {
		$done = false;
		foreach ( $this->conf['js'] as $key => $val ) {
			if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
				continue;
			}
			$done = true;
			$p = $val['args'];

			if ( $p['ab'] ) {
	#			$ret = $this->shell_exec( "uglifyjs $in --verbose -o $out 2>&1" );
				$ret .= $this->shell_exec( "java -jar /usr/lib/wexal/gcc/closure-compiler-v20190819.jar -O WHITESPACE_ONLY --js $in --js_output_file $esc_a 2>&1" );
	#			$ret .= $this->shell_exec( "java -jar /usr/lib/wexal/gcc/closure-compiler-v20190819.jar -O SIMPLE_OPTIMIZATIONS --js $in --js_output_file $esc_b 2>&1" );
				$ret .= $this->shell_exec( "/usr/local/bin/terser $in --verbose -o $esc_b 2>&1", 'nodejs', array( 'terser', $in, $esc_b ) );
				$this->rename_by_ab_test( $a, $b, $opt_path );
			} else {
				$ret .= $this->shell_exec( "/usr/local/bin/terser $in --verbose -o $out 2>&1", 'nodejs', array( 'terser', $in, $out ) );
			}

			$ie_path = preg_replace( '#\.opt\.js$#', '.ie.js', $opt_path );
			$a = preg_replace( '#\.([^.]+)$#', '.a.$1', $ie_path );
			$b = preg_replace( '#\.([^.]+)$#', '.b.$1', $ie_path );
			$esc_a = escapeshellarg( $a );
			$esc_b = escapeshellarg( $b );
			$esc_ie = escapeshellarg( $ie_path );

			if ( $p['ie11_ab'] && is_file( $out ) ) {
				$ret .= $this->shell_exec( "/usr/local/bin/babel --no-babelrc --minified --presets /usr/local/lib/node_modules/babel-preset-env $out -o $esc_a 2>&1", 'nodejs', array( 'babel', $out, $esc_a, '/usr/local/lib/node_modules/babel-preset-env') );
				$ret .= $this->shell_exec( "/usr/local/bin/babel --no-babelrc --minified --presets /usr/local/lib/node_modules/babel-preset-env $in -o $esc_b 2>&1", 'nodejs', array( 'babel', $in, $esc_b, '/usr/local/lib/node_modules/babel-preset-env') );
				$this->rename_by_ab_test( $a, $b, $ie_path );
			} else {
				if ( $p['ie11'] ) {
					$ret .= $this->shell_exec( "/usr/local/bin/babel --no-babelrc --minified --presets /usr/local/lib/node_modules/babel-preset-env $in -o $esc_ie 2>&1", 'nodejs', array( 'babel', $in, $esc_ie, '/usr/local/lib/node_modules/babel-preset-env') );
				} else {
					copy( $opt_path, $ie_path );
					$this->if_larger_than_org( $file_path, $ie_path, true );
				}
			}

			$this->if_larger_than_org( $file_path, $opt_path, true );
		}
	} elseif ( '.opt.css' == $newtype ) {
		$done = false;
		foreach ( $this->conf['css'] as $key => $val ) {
			if ( $done || false ==  $this->is_apply( $val['apply'], $val['exclude'], $file_path ) ) {
				continue;
			}
			$done = true;
			$p = $val['args'];

			if ( 'uncss' == $this->method ) {
				$conf_file = $this->get_config_file( 'js/uncss/postcss.config.js' );
				$conf_file = escapeshellarg( $conf_file );
				$ret .= $this->shell_exec( "/usr/local/bin/postcss $in --config $conf_file --no-map -o $out 2>&1", 'nodejs', array( 'postcss', $in, $out, $conf_file ) );
			} else {
				if ( $p['ab'] ) {
					$conf_file = $this->get_config_file( 'js/cssnano/postcss.config.js' );
					$conf_file = escapeshellarg( $conf_file );
					$ret .= $this->shell_exec( "/usr/local/bin/postcss $in --config $conf_file --no-map -o $esc_a 2>&1", 'nodejs', array( 'postcss', $in, $esc_a, $conf_file ) );
					$ret .= $this->shell_exec( "/usr/local/bin/cleancss -O2 $in -o $esc_b 2>&1", 'nodejs', array( 'cleancss', $in, $esc_b ) );
					$this->rename_by_ab_test( $a, $b, $opt_path );
				} else {
					$conf_file = $this->get_config_file( 'js/cssnano/postcss.config.js' );
					$conf_file = escapeshellarg( $conf_file );
					$ret .= $this->shell_exec( "/usr/local/bin/postcss $in --config $conf_file --no-map -o $out 2>&1", 'nodejs', array( 'postcss', $in, $out, $conf_file ) );
				}

				$ie_path = preg_replace( '#\.opt\.css$#', '.ie.css', $opt_path );
				$a = preg_replace( '#\.([^.]+)$#', '.a.$1', $ie_path );
				$b = preg_replace( '#\.([^.]+)$#', '.b.$1', $ie_path );
				$esc_a = escapeshellarg( $a );
				$esc_b = escapeshellarg( $b );
				$esc_ie = escapeshellarg( $ie_path );

				$conf_a = $this->get_config_file( 'js/autoprefixer/a/postcss.config.js' );
				$conf_a = escapeshellarg( $conf_a );
				$conf_b = $this->get_config_file( 'js/autoprefixer/b/postcss.config.js' );
				$conf_b = escapeshellarg( $conf_b );

				if ( $p['ie11_ab'] && is_file( $out ) ) {
					$ret .= $this->shell_exec( "/usr/local/bin/postcss $in --config $conf_a --no-map -o $esc_a 2>&1", 'nodejs', array( 'postcss', $in, $esc_a, $conf_a ) );
					$ret .= $this->shell_exec( "/usr/local/bin/postcss $out --config $conf_b --no-map -o $esc_b 2>&1", 'nodejs', array( 'postcss', $out, $esc_b, $conf_b ) );
					$this->rename_by_ab_test( $a, $b, $ie_path );
				} else {
					if ( $p['ie11'] ) {
						$ret .= $this->shell_exec( "/usr/local/bin/postcss $in --config $conf_a --no-map -o $esc_ie 2>&1", 'nodejs', array( 'postcss', $in, $esc_ie, $conf_a ) );
					} else {
						copy( $opt_path, $ie_path );
						$this->if_larger_than_org( $file_path, $ie_path, true );
					}
				}

			}
			$this->if_larger_than_org( $file_path, $opt_path, true );
		}
	}

	if ( $ret ) {
		$this->log( $ret );
	}
	if ( is_file( $opt_path ) ) {
		touch( $opt_path, $org_time );
	}
}

function get_pathinfo( $file_path, $key ) {
	$tmp = pathinfo( $file_path );
	$def = array( 'extension' => '' );
	$tmp = array_merge( $def, $tmp );
	return $tmp[ $key ];
}

function get_optimize_path( $file_path ) {

	$docroot = $this->pst->DocumentRoot;
	$drq = preg_quote( $docroot, '#' );
	$odir = $this->pst->wexal_dir . '/optdir';
	$tmp = preg_replace( "#^$drq#" , '', $file_path );
	$opt_path_without_ext = $odir . $tmp;

	return $opt_path_without_ext;
}

function delete_cache( $file_path ) {
	$opt_path = $this->get_optimize_path( $file_path );

	if ( ! preg_match ( '#^/home/kusanagi/[^/]+/wexal/optdir/#', $opt_path ) ) { return; }

	if ( preg_match( '#/\.{2,}#', $opt_path ) ) { return; }
	if ( preg_match( '#/_wexal.org#', $opt_path ) ) { return; }

	if ( is_dir( $opt_path ) ) {
		$cmd = "/bin/rm -r $opt_path";
		$this->log( $cmd );
		$ret = shell_exec( $cmd );
	} else {
		$reg = preg_quote( $opt_path, "#" );
		$tmp = glob ( $opt_path . '*' );
		foreach ( $tmp as $path ) {
			if ( preg_match( "#^$reg\.?(opt.css|opt.js|ie.css|ie.js|webp|jp2|jxr)?#", $path ) && is_file( $path ) ) {
				unlink( $path );
				$this->log( "unlink $path" );
			}
		}
	}
}

function moved_from( $file_path, $event, $cookie ) {
	if ( $cookie ) {
		$opt_path = $this->get_optimize_path( $file_path );
		if ( "MOVED_FROM,ISDIR" == $event && is_dir( $opt_path ) ) {
			$t = $this->pst->wexal_dir . "/tmpdir/$cookie";
			rename( $opt_path, $t );
			$this->log( "rename $opt_path => $t" );
		} else {
			$this->delete_cache( $file_path );
		}
	}
}

function moved_to( $file_path, $event, $cookie ) {
	if ( $cookie ) {
		$opt_path = $this->get_optimize_path( $file_path );
		if ( "MOVED_TO,ISDIR" == $event ) {
			$t = $this->pst->wexal_dir . "/tmpdir/$cookie";
			if ( is_dir( $t ) && ! is_dir( $opt_path ) ) {
				if ( ! is_dir( dirname( $opt_path ) ) ) {
					mkdir ( dirname( $opt_path ), 0755, true );
				}
				rename( $t, $opt_path );
				$this->log( "rename $t => $opt_path" );
			} else {
				$this->optimize( $file_path );
			}
		} elseif ( "MOVED_TO" == $event ) {
			$this->opt_file( $file_path );
		}
	}
}

function get_opt_exts() {
	if ( isset($this->opt_exts) ) {
		return $this->opt_exts;
	}
	$this->opt_exts = array();
	if ( isset($this->conf['img'][''] ) && is_array( $this->conf['img'][''] ) ) {
		$this->opt_exts = $this->img_ext;
	}
	if ( isset($this->conf['img']['.webp'] ) && is_array( $this->conf['img']['.webp'] ) ) {
		$this->opt_exts[] = 'webp';
	}
	if ( isset($this->conf['img']['.jp2'] ) && is_array( $this->conf['img']['.jp2'] ) ) {
		$this->opt_exts[] = 'jp2';
	}
	if ( isset($this->conf['img']['.jxr'] ) && is_array( $this->conf['img']['.jxr'] ) ) {
		$this->opt_exts[] = 'jxr';
	}
	if ( isset($this->conf['css'] ) && is_array( $this->conf['css'] ) ) {
		$this->opt_exts[] = 'css';
	}
	if ( isset($this->conf['js'] ) && is_array( $this->conf['js'] ) ) {
		$this->opt_exts[] = 'js';
	}
	return $this->opt_exts;
}

function purge( $dir = false ) {
	$exculde_reg = '#(' . join( '|', $this->pst->config['global_exclude']) . ')#';
	if ( false == $dir ) {
		$dir = $this->odir;
	}
	if ( is_dir( $dir ) && $handle = opendir( $dir ) ) {
		while ( ( $file = readdir( $handle ) ) !== false ) {
			if ( $file == '.' || $file == '..' ) {
				continue;
			}
			$filepath = $dir == '.' ? $file : $dir . '/' . $file;
			if ( is_link( $filepath ) || preg_match( '#(_wexal\.org|_wxpdir|out)#', $filepath ) ) {
				continue;
			}
			if ( is_file( $filepath ) ) {
				$this->purge_file( $filepath, $exculde_reg );
			} 
			else if ( is_dir( $filepath ) ) {
				if ( ! $this->purge_dir( $filepath, $exculde_reg ) ) {
					$this->purge( $filepath );
				}
			}
		}
		closedir($handle);
	}
}


function purge_dir( $dir, $exculde_reg ) {
	$tmp_dir = preg_replace( "#{$this->odir}#", '', $dir );
	$org_dir = $this->tdir . $tmp_dir;
	if ( preg_match( $exculde_reg, $tmp_dir ) || ! file_exists( $org_dir ) ) {
		$cmd = "/bin/rm -rf {$dir}";
		$this->log( "purge : {$dir}" );
		shell_exec( $cmd );
		return true;
	}
	return false;
}

function purge_file( $filepath, $exculde_reg ) {
	if ( ! is_file( $filepath ) ) {
		return false;
	}
	$nolarge_filepath = preg_replace( "#\.(large)$#", '', $filepath );
	$tmp_file = preg_replace( '#'.preg_quote($this->odir).'#', '', $nolarge_filepath );
	$tmp_file = preg_replace( "#\.(ie\.css|opt\.css|ie\.js|opt\.js|webp|jxr|jp2)$#", '', $tmp_file );
	$org_file = $this->tdir . $tmp_file;
	if ( ! file_exists( $org_file ) ) {
		unlink( $filepath );
		$this->log( "purge : {$filepath}" );
		return true;
	}
	$org_time = filemtime( $org_file );
	$opt_time = filemtime( $filepath );
	if ( $opt_time < $org_time ) {
		unlink( $filepath );
		$this->log( "purge : {$filepath}" );
		return true;
	}
	$opt_parts = pathinfo( $nolarge_filepath );
	if ( ! in_array( $opt_parts['extension'], $this->get_opt_exts() ) ) {
		unlink( $filepath );
		$this->log( "purge : {$filepath}" );
		return true;
	} elseif ( in_array( $opt_parts['extension'], $this->img_ext ) )  {
		$apply = false;
		foreach ( $this->conf['img'][''] as $key => $param ) {
			if ( $this->is_apply( $param['apply'], $param['exclude'], $filepath ) ) {
				$apply = true;
				break;
			}
		}
		if ( false == $apply ) {
			unlink( $filepath );
			$this->log( "purge : {$filepath}" );
			return true;
		}
	} elseif ( in_array( $opt_parts['extension'], array( 'webp', 'jp2', 'jxr' ) ) )  {
		$new_ext = '.'.$opt_parts['extension'];
		$apply = false;
		foreach ( $this->conf['img'][$new_ext] as $key => $param ) {
			if ( $this->is_apply( $param['apply'], $param['exclude'], $filepath ) ) {
				$apply = true;
				break;
			}
		}
		if ( false == $apply ) {
			unlink( $filepath );
			$this->log( "purge : {$filepath}" );
			return true;
		}
	} elseif ( in_array( $opt_parts['extension'], array( 'css', 'js' ) ) ) {
		$new_ext = $opt_parts['extension'];
		$apply = false;
		foreach ( $this->conf[$new_ext] as $key => $param ) {
			if ( $this->is_apply( $param['apply'], $param['exclude'], $filepath ) ) {
				$apply = true;
				break;
			}
		}
		if ( false == $apply ) {
			unlink( $filepath );
			$this->log( "purge : {$filepath}" );
			return true;
		}
	}
}

function log( $log ) {
	$logfile = $this->pst->logdir . '/page_speed_technology.log';
	error_log( 'worker ' . date( "Y-m-d H:i:s" ) . ' ' . $log . "\n", 3, $logfile );
}

function check_time_stamp( $file_path, $opt_path ) {
	$large = $opt_path . '.large';
	$org_time = filemtime( $file_path );

	if ( true == $this->force ) {
		return $org_time;
	}

	if ( is_file( $opt_path ) && $org_time == filemtime( $opt_path ) ) {
		return false;
	} elseif ( is_file( $large ) && $org_time == filemtime( $large ) ) {
		return false;
	} else {
		return $org_time;
	}
}

function shell_exec( $shell_cmd, $backend = '', $backend_cmd = array() ) {
	if ( $this->conf['benchmark'] ) {
		$time_start = microtime( TRUE );
	}

	if ( $backend && false == $this->backend[ $backend ] ) {
		$backend = '';
	}
	switch ( $backend ) {
		case 'nodejs':
			$ret = $this->backend_nodejs_exec( $backend_cmd );
			break;
		default:
			$ret = shell_exec( $shell_cmd );
			break;
	}

	if ( $this->conf['benchmark'] ) {
		$time_end = microtime( TRUE );
		$time = $time_end - $time_start;
		if ( $backend ) {
			$this->log( "benchmark [$backend] $time " . join( ' ', $backend_cmd ) );
		} else {
			$this->log( "benchmark [shell_exec] $time $shell_cmd" );
		}
	}

	if ( false == $this->conf['debug'] ) {
		$ret = '';
	}
	return $ret;
}

function backend_nodejs_exec( $cmd ) {
	$def = array( '_#_#_#_', '_#_#_#_', '_#_#_#_', '' );
	$merge = array();
	foreach ( $def as $key => $val ) {
		$merge[] = isset( $cmd[ $key ] ) ? $cmd[ $key ] : $val;
	}
	list( $command, $input, $output, $config ) = $merge;

	$input  = preg_replace( "#^'(.+)'$#", '$1', $input );
	$output = preg_replace( "#^'(.+)'$#", '$1', $output );
	$config = preg_replace( "#^'(.+)'$#", '$1', $config );

	$json   = json_encode( array(
		'input'  => $input,
		'output' => $output,
		'config' => $config
	) );

	$ch = curl_init( $this->backend['nodejs'] . '/' . $command );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
	curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
	curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen( $json )
	) );
	$ret = curl_exec( $ch );
	curl_close( $ch );

	return $ret;
}

} // class end

