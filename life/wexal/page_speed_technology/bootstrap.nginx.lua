ngx.ctx.init = function ()
	if nil == ngx.ctx.pst then
		-- Phase must be header_filter --
		-- initialize pst --
		local m = ngx.re.match( ngx.ctx.file, '/home/kusanagi/([^/]+)/' )
		local profile = m[1]
		local pst = {
			fx = {
				match  = function( subject, regex, options )
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					return ngx.re.match( subject, regex, options ) end,
				gmatch  = function( subject, regex, options )
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					return ngx.re.gmatch( subject, regex, options ) end,
				find   = function( subject, regex, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					return ngx.re.find( subject, regex, options ) end,
				sub   = function( subject, regex, replace, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					return ngx.re.sub( subject, regex, replace, options ) end,
				gsub   = function( subject, regex, replace, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					return ngx.re.gsub( subject, regex, replace, options ) end,
				log    = function( mes, pst, level )
					if nil == level then level = 'ALERT' end
					level = tostring( level )
					mes = tostring( mes )
					local array = { 'STDERR', 'EMERG', 'ALERT', 'CRIT', 'ERR', 'WARN', 'NOTICE', 'INFO', 'DEBUG' }
					local key = pst.lib.in_array( level, array )
					if key then	ngx.log( ngx[ array[ key ] ], mes )	end end,

				fcache = function( cmd, pst )

					local do_not_cache          = tostring( ngx.var.do_not_cache )
					if '0' ~= do_not_cache then return false end

					if 'get' == cmd then 
						if pst.body_cache then
							ngx.arg[1] = pst.body_cache
							ngx.arg[2] = true
							return true
						else
							return false
						end
					end

					local upstream_cache_status = ngx.var.upstream_cache_status
					local fastcgi_cache_key     = ngx.var.fastcgi_cache_key
					local wexal                 = ngx.shared.wexal
					local fcache = tostring( pst.conf.lua.fcache.enable )
					if '1' ~= fcache then fcache = false end
					local exptime = tonumber( pst.conf.lua.fcache.exptime )
					if 'number' ~= type( exptime ) then exptime = 60 end

					if 'set' == cmd and fcache then
						wexal:flush_expired( 10 )
						wexal:set( pst.profile .. ':body:' .. fastcgi_cache_key, pst.body, exptime )
						return
					end
					
					if 'head' == cmd then
						local bc = ':B=' .. tostring( pst.header['x-b-cache'] )
						if fcache and 'HIT' == upstream_cache_status then
							pst.body_cache = wexal:get( pst.profile .. ':body:' .. fastcgi_cache_key )
							if pst.body_cache then
								pst.header['x-b-cache'] = 'L=HIT' .. bc
							else
								pst.header['x-b-cache'] = 'L=MISS/CREATE' .. bc
							end
						elseif fcache then
							pst.header['x-b-cache'] = 'L=CREATE' .. bc
						else
							pst.header['x-b-cache'] = 'L=BYPASS' .. bc
						end
					end
				end,

			},
			profile     = profile,
			wexal_dir   = '/home/kusanagi/' .. profile .. '/wexal',
			phase       = ngx.get_phase(),
			header      = ngx.header,
			host        = ngx.var.host,
			https       = ngx.var.https,
			request_uri = ngx.var.request_uri,
			ext         =  '',
			is_user_logged_in = false,
			lyaml       = require 'lyaml',
		}

		-- check phase --
		if 'header_filter' ~= pst.phase then return false end

		-- load library --
		local parent = pst.wexal_dir .. '/page_speed_technology/lib_for_lua.lua'
		local child  = pst.wexal_dir .. '/userdir/lib_for_lua.lua'
		pst.lib = loadfile( parent )()
		if pst.lib.file_exists( child ) then
			local fx = loadfile( child )
			local user = pst.lib.try( fx, pst )
			if 'table' == type( user ) then
				for key, val in pairs( user ) do
					pst.lib[ key ] = val
				end
			end
		end
		pst.version = pst.lib.version

		-- load pst.config.yaml --
		pst.lib.try ( 
			function()
				local ret = pst.lib.file_get_contents( pst.wexal_dir .. '/pst.config.yaml' )
				pst.conf = pst.lyaml.load ( ret )
			end, pst
		)

		-- check config --
		if 'table' ~= type( pst.conf ) then	return false end
		if 'on' ~= pst.conf.pst then return false end
		if 'table' ~= type( pst.conf.options ) then	return false end
		if false == pst.lib.in_array( 'lua', pst.conf.options ) then return	false end
		if false == lua then return false end
		if 'table' ~= type( pst.conf.lua ) then return false end
		if 'table' ~= type( pst.conf.lua.header_filter ) then pst.conf.lua.header_filter = {} end
		if 'table' ~= type( pst.conf.lua.body_filter ) then pst.conf.lua.body_filter = {} end
		if 'table' ~= type( pst.conf.global_exclude ) then pst.conf.global_exclude = { '_wexal' } end
		if 'table' ~= type( pst.conf.lua.fcache ) then pst.conf.lua.fcache = { enable = 1, exptime = 60 } end
	
		-- check global exclude --
		local exclude = '(' .. pst.lib.join( '|', pst.conf.global_exclude ) .. ')'
		if pst.fx.match( pst.request_uri, exclude ) then
			return false
		end

		-- check is_user_logged_in and next generation image extension by user environment --
		local hash = pst.fx.gsub( pst.profile, [[\.]], '_d_' )
		hash = pst.fx.gsub( hash, '-', '_h_' )
		local _ = ngx.var[ 'org_' .. hash ]
		if '/_wexal.dummy' == _ then _ = false else _ = true end
		if true == _ and false == pst.lib.in_array( 'apply_logged_in_user', pst.conf.options ) then
			return false
		end
		pst.is_user_logged_in = _
		pst.ext = ngx.var[ 'ext_' .. hash ]

		ngx.ctx.pst = pst
	else

		-- Phase must be body_filter --
		if nil == ngx.ctx.pst then return false end
		local pst = ngx.ctx.pst
		pst.phase = ngx.get_phase()

		if pst.fx.fcache( 'get', pst ) then return false end

		local chunk, eof = ngx.arg[1], ngx.arg[2]
		if nil == ngx.ctx.buf then ngx.ctx.buf = {} end
		local buf = ngx.ctx.buf
		if chunk ~= "" then
			buf[ #buf + 1 ] = chunk
			ngx.arg[1] = nil
		end
		if eof then
			pst.body = table.concat( buf )
			ngx.arg[1] = pst.body
			pst.fx.body = function( arg, pst )
				pst.body = arg
				ngx.arg[1] = arg
			end
		end
	end
	-- finally --
	return ngx.ctx.pst
end

local main = function()
	if 'off' == ngx.var.arg_pst then return false end
	-- init --
	local pst = ngx.ctx.init()
	if false == pst then return false end

	-- pre filter --
	if 'header_filter' == pst.phase then
		pst.fx.fcache( 'head', pst )
	elseif 'body_filter' == pst.phase then
		if not pst.body or '' == pst.body then return false end
	end

	-- main --
	local cmds = pst.conf.lua[ pst.phase ]
	for key, val in ipairs( cmds ) do
		if  nil ~= val.cmd then
			local cmd = val.cmd
			local args
			local apply = { '.' }
			local exclude = {}
			if 'table' == type( val.apply ) then apply = val.apply end
			if 'table' == type( val.exclude ) then exclude = val.exclude end
			if true == pst.lib.is_apply( { apply = apply, exclude = exclude }, pst ) then
				cmd = pst.phase .. '_' .. pst.fx.gsub( cmd, ' ', '_' )
				if 'table' == type( val.args ) then
					args = val.args
				elseif 'nil' == type( val.args ) then
					args = { '' }
				else
					args = { tostring( val.args ) }
				end
				if 'function' == type( pst.lib[ cmd ] ) then
					pst.lib.try( 
						function()
							pst.lib[ cmd ]( args, pst )
						end, pst
					)
				end
			end
		end
	end

	-- after filter --
	if 'header_filter' == pst.phase then

	elseif 'body_filter' == pst.phase then
		pst.lib.wexal_pst_init( pst )
		pst.fx.fcache( 'set', pst )
	end
end

main()

