<?php

$str = yaml_parse_file( dirname( dirname( __FILE__ ) ) . '/pst.config.yaml' );
print_r( $str );
