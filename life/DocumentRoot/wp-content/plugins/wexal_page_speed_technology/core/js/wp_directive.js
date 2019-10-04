var editor = new JSONEditor(document.getElementById('editor_holder'),{
	schema: {
		type: "object",
		title: "wp",
		properties: {

			wexal_init: {
				propertyOrder: 100,
				options: {
					collapsed: true
				},
				type: "array",
				items: {
					type: "object",
					title: "directive",
					properties: {
						cmd_presets: {
							propertyOrder: 500,
							type: "string",
							enum: [ "remove header" ],
							options: {
								dependencies: {
									cmd: ""
								}
							}
						},
						cmd: {
							propertyOrder: 100,
							type: "string",
						},
						apply: {
							propertyOrder: 200,
							$ref: "#/definitions/apply_exclude"
						},
						exclude: {
							propertyOrder: 300,
							$ref: "#/definitions/apply_exclude"
						},
						args: {
							propertyOrder: 400,
							type: [ "array", "object" ],
						},
						args_remove_header: {
							$ref: "#/definitions/args_remove_header",
							options: {
								dependencies: {
									cmd_presets: "remove header"
								}
							}
						},
					},
					defaultProperties: [ "cmd_presets" ],
				}
			},

			wexal_enqueue_opt: {
				propertyOrder: 200,
				options: {
					collapsed: true
				},
				type: "array",
				items: {
					type: "object",
					title: "directive",
					properties: {
						cmd_presets: {
							propertyOrder: 500,
							type: "string",
							enum: [
								"remove emoji",
								"remove meta",
								"remove js",
								"add js",
								"remove css",
								"add css",
								"remove hook",
								"opt genericons",
								"remove wpcf7"
							],
							options: {
								dependencies: {
									cmd: ""
								}
							}
						},
						cmd: {
							propertyOrder: 100,
							type: "string",
						},
						apply: {
							propertyOrder: 200,
							$ref: "#/definitions/apply_exclude"
						},
						exclude: {
							propertyOrder: 300,
							$ref: "#/definitions/apply_exclude"
						},
						args: {
							propertyOrder: 400,
							type: [ "array", "object" ],
						},
						args_remove_meta: {
							$ref: "#/definitions/args_remove_meta",
							options: {
								dependencies: {
									cmd_presets: "remove meta"
								}
							}
						},
						args_remove_js: {
							$ref: "#/definitions/args_array_string",
							options: {
								dependencies: {
									cmd_presets: "remove js"
								}
							}
						},
						args_add_js: {
							$ref: "#/definitions/args_add_js",
							options: {
								dependencies: {
									cmd_presets: "add js"
								}
							}
						},
						args_remove_css: {
							$ref: "#/definitions/args_array_string",
							options: {
								dependencies: {
									cmd_presets: "remove css"
								}
							}
						},
						args_add_css: {
							$ref: "#/definitions/args_add_css",
							options: {
								dependencies: {
									cmd_presets: "add css"
								}
							}
						},
						args_remove_hook: {
							$ref: "#/definitions/args_remove_hook",
							options: {
								dependencies: {
									cmd_presets: "remove hook"
								}
							}
						},
					},
					defaultProperties: [
						"cmd_presets",
						"args_remove_js",
						"args_add_js",
						"args_remove_css",
						"args_add_css",
						"args_remove_hook",
					],
				}
			},

			wexal_head: {
				propertyOrder: 300,
				options: {
					collapsed: true
				},
				type: "array",
				items: {
					type: "object",
					title: "directive",
					properties: {
						cmd_presets: {
							propertyOrder: 500,
							type: "string",
							enum: [ "preload" ],
							options: {
								dependencies: {
									cmd: ""
								}
							}
						},
						cmd: {
							propertyOrder: 100,
							type: "string",
						},
						apply: {
							propertyOrder: 200,
							$ref: "#/definitions/apply_exclude"
						},
						exclude: {
							propertyOrder: 300,
							$ref: "#/definitions/apply_exclude"
						},
						args: {
							propertyOrder: 400,
							type: [ "array", "object" ],
						},
						args_preload: {
							$ref: "#/definitions/args_preload",
							options: {
								dependencies: {
									cmd_presets: "preload"
								}
							}
						},
					},
					defaultProperties: [ "cmd_presets", "args_preload" ],
				}
			},

			wexal_footer: {
				propertyOrder: 400,
				options: {
					collapsed: true
				},
				type: "array",
				items: {
					type: "object",
					title: "directive",
					properties: {
						cmd_presets: {
							propertyOrder: 500,
							type: "string",
							enum: [],
							options: {
								dependencies: {
									cmd: ""
								}
							}
						},
						cmd: {
							propertyOrder: 100,
							type: "string",
						},
						apply: {
							propertyOrder: 200,
							$ref: "#/definitions/apply_exclude"
						},
						exclude: {
							propertyOrder: 300,
							$ref: "#/definitions/apply_exclude"
						},
						args: {
							propertyOrder: 400,
							type: [ "array", "object" ],
						},
					},
					defaultProperties: [ "cmd_presets" ],
				}
			},

			wexal_flush: {
				propertyOrder: 500,
				options: {
					collapsed: true
				},
				type: "array",
				items: {
					type: "object",
					title: "directive",
					properties: {
						cmd_presets: {
							propertyOrder: 500,
							type: "string",
							enum: [
								"server push external css",
								"shorten url",
								"defer external js",
								"set cookie for cdn",
								"replace",
								"replace anything",
								"lazy youtube",
								"engagement delay",
							],
							options: {
								dependencies: {
									cmd: ""
								}
							}
						},
						cmd: {
							propertyOrder: 100,
							type: "string",
						},
						apply: {
							propertyOrder: 200,
							$ref: "#/definitions/apply_exclude"
						},
						exclude: {
							propertyOrder: 300,
							$ref: "#/definitions/apply_exclude"
						},
						args: {
							propertyOrder: 400,
							type: [ "array", "object" ],
						},
						args_engagement_delay: {
							$ref: "#/definitions/args_engagement_delay",
							options: {
								dependencies: {
									cmd_presets: "engagement delay"
								}
							}
						},
					},
					defaultProperties: [ "cmd_presets", "args_engagement_delay" ],
				}
			},

		},

		definitions: {
			apply_exclude: {
				type: "object",
				properties: {
					if: {
						type: [ "string", "array", "object" ],
						default: {},
						items: {
							type: "string",
							default: "",
						},
						properties: {
							is_front_page: { $ref: "#/definitions/if_string" },
							is_page:       { $ref: "#/definitions/if_string_array" },
							is_single:     { $ref: "#/definitions/if_string_array" },
							is_singular:   { $ref: "#/definitions/if_string_array" },
							is_archive:    { $ref: "#/definitions/if_string" },
							is_category:   { $ref: "#/definitions/if_string_array" },
							in_category:   { $ref: "#/definitions/if_string_array" },
						},
						defaultProperties: [],
					},
					path: {
						type: [ "string", "array" ],
						default: "",
						items: {
							type: "string",
							default: "",
						},
					},
				}
			},
			if_string_array: {
				type: [ "string", "array" ],
				default: "",
				items: {
					type: "string",
					default: "",
				}, 
			},
			if_string: {
				type: "string",
				default: "",
			},
			args_remove_header: {
				type: "object",
				properties: {
					pings_open: { type: "boolean", default: true },
					rest_output_link_header: { type: "string", default: 11 },
					wp_shortlink_header: { type: "string", default: 11 },
				}
			},
			args_remove_meta: {
				type: "object",
				properties: {
					feed_links : { type: "string", default: 2},
					feed_links_extra : { type: "string", default: 3},
					rsd_link : { type: "string", default: 10},
					wlwmanifest_link : { type: "string", default: 10},
					adjacent_posts_rel_link_wp_head : { type: "string", default: 10},
					rest_output_link_wp_head : { type: "string", default: 10},
					wp_oembed_add_discovery_links : { type: "string", default: 10},
					wp_oembed_add_host_js : { type: "string", default: 10},
					wp_shortlink_wp_head : { type: "string", default: 10},
					rel_canonical : { type: "string", default: 10},
					wp_generator : { type: "string", default: 10},
				}
			},
			args_preload: {
				type: "array",
				default: [ '' ],
				items: {
					type: [ "string", "object" ],
				}
			},
			args_array_string: {
				type: "array",
				default: [ '' ],
				items: {
					type: "string",
				}
			},
			args_add_js: {
				type: "array",
				default: [ "", "", [], "", false ],
				items: {
					type: [ "string", "boolean", "array" ]
				},
			},
			args_add_css: {
				type: "array",
				default: [ "", "", [], "", "all" ],
				items: {
					type: [ "string", "boolean", "array" ]
				},
			},
			args_remove_hook: {
				type: "array",
				default: [ "", "", 10, false ],
				items: {
					type: [ "string", "boolean", "array" ]
				},
			},
			args_engagement_delay: {
				type: "object",
				format: "categories",
				properties: {
					score: { type: "string", default: 250 },
					pscore: { type: "string", default: 10 },
					high: { 
						type: "string", 
						enum: [ 'body', 'DOMContentLoaded', 'load' ],
						default: "DOMContentLoaded"
					},
					low: { 
						type: "string",
						enum: [ 'body', 'DOMContentLoaded', 'load' ],
						default: "load"
					},
					delay: { type: "string", default: "1000" },
					ratio: { type: "string", default: 4 },
					debug: { type: "boolean", default: false },
					"max-age": { type: "string", default: 7776000},
					inline: { type: "boolean", default: false },
					scripts: { 
						type: "array",
						default: [{
							type: "closure",
						}],
						items: {
							type: 'object',
							format: "categories",
							defaultProperties: [ "type", "needle", "pattern", "args", "path", "cmd" ],
							properties: {
								name:    { type: "string", default: '' },
								type:    { 
									type: "string",
									enum: [ 'inline js', 'inline jsx', 'closure', 'closurex', 'js', 'jsx', 'css', 'cssx' ],
									default: 'closure'
								},
								needle:	 {
									type: "string",
									default: '',
									options: {
										dependencies: {
											type: [ "inline js", "inline jsx"]
										}
									}
								},
								pattern: { 
									type: "string",
									default: '',
									options: {
										dependencies: {
											type: [ "closure", "closurex"]
										}
									}
								},
								args:    { 
									type: "string",
									default: '',
									options: {
										dependencies: {
											type: [ "closure", "closurex"]
										}
									}
								},
								path:    {
									type: "string",
									default: '',
									options: {
										dependencies: {
											type: [ "js", "jsx", "css", "cssx" ]
										}
									}
								},
								query:   { 
									type: "string",
									enum: [ 'auto', 'none' ],
									default: 'auto',
									options: {
										dependencies: {
											type: [ "js", "jsx", "css", "cssx" ]
										}
									}
								},
								sync:    { 
									type: "string",
									enum: [ 'sync', 'async' ],
									default: 'sync',
									options: {
										dependencies: {
											type: [ "js", "jsx", "css", "cssx" ]
										}
									}
								},
								cmd:     {
									type: "array",
									default: [],
									items: {
										type: 'object',
										properties: {
											function: { type: "string", default: '' },
											args:     { type: "string", default: '' },
										},
									},
									options: {
										dependencies: {
											type: [ "closure", "closurex" ]
										}
									}
								},
							},
						},
					},
				}
			},
		},
	},
	theme: 'jqueryui',
});

!function(){
	var conf = <?php echo $conf_wp_stg; ?>;
	var ds = [ 'wexal_init', 'wexal_enqueue_opt', 'wexal_head', 'wexal_footer', 'wexal_flush' ];

	for ( var i=0; i < ds.length; i++ ) {
		if ( null == conf[ ds[i] ] || false == Array.isArray( conf[ ds[i] ] ) ) {
			conf[ ds[i] ] == [];
		}
		var d = conf[ ds[i] ];
		var s = editor.schema.properties[ ds[i] ];
		var a = s.items.properties;
		s = s.items.properties.cmd_presets.enum;
		for ( var j=0; j < d.length; j++ ) {
			var obj = d[j];
			if ( obj.cmd && s.indexOf( obj.cmd ) >=0 ) {
				conf[ ds[i] ][j]['cmd_presets'] = obj.cmd;
				var args_cmd = 'args_' + obj.cmd.replace( / /g, '_' );
				delete conf[ ds[i] ][j]['cmd'];
				if ( a[ args_cmd ] && obj.args ) {
					conf[ ds[i] ][j][ args_cmd ] = obj.args;
					delete conf[ ds[i] ][j]['args'];
				}
			}
		}
	}

	editor.setValue( conf );

	document.getElementById( 'submit1' ).addEventListener( 'click', function() {
//		console.log(editor.getValue());
		var json = editor.getValue();
		for ( var i=0; i < ds.length; i++ ) {
			if ( null == json[ ds[i] ] || false == Array.isArray( json[ ds[i] ] ) ) {
				json[ ds[i] ] = [];
			}
			var d = json[ ds[i] ];
			for ( var j=0; j < d.length; j++ ) {
				var obj = d[j];
				if ( null == obj.cmd || '' == obj.cmd ) {
					if ( obj.cmd_presets ) {
						json[ ds[i] ][j]['cmd'] = obj.cmd_presets;
						var pa = 'args_' + obj.cmd_presets;
						pa = pa.replace( / /g, '_' );
						//delete json[ ds[i] ][j]['cmd_presets'];
						if ( null == obj.args || false == obj.args ) {
							if ( obj[ pa ] ) {
								json[ ds[i] ][j]['args'] = obj[ pa ];
							//	delete json[ ds[i] ][j][ pa ];
							}
						}
					}
				}
			}
			for ( var j=0; j < d.length; j++ ) {
				var obj = d[j];
				for ( var k of Object.keys( obj ) ) {
					if ( k.match( /^args_/ ) ) {
						delete json[ ds[i] ][j][k];
					}
					if ( 'cmd_presets' == k ) {
						delete json[ ds[i] ][j][k];
					}
				}
			}
		}

		var output = document.getElementById( 'wexal_pst_conf_wp_stg' );
		output.value = JSON.stringify( json, null, 2 );
	});
}();
