local httpd = {
	ctx = {},
	phase = 'header_filter'
}
httpd.set_phase = function( p )
	httpd.phase = p
end
httpd.get_phase = function()
	return httpd.phase
end

httpd.ctx.init = function ( r )
	if nil == httpd.ctx.pst then
		-- Phase must be header_filter --
		-- initialize pst --
		local rex = require 'rex_pcre'
		local profile = rex.match( r.document_root, '/home/kusanagi/([^/]+)/' )
		local pst = {
			fx = {
				match  = function( subject, regex, options )
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					local status, m = pcall( function()
						local r = '(' .. regex .. ')'
						local m = { rex.match( subject, r, 1, options ) }
						if 0 == #m then
							return nil
						else
							for i = 1, #m do
								m[ i - 1 ] = m[ i ]
							end
							m[ #m ] = nil
							return m
						end
					end)
					if status then
						return m
					else
						return nil
					end
				end,
				gmatch = function( subject, regex, options )
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					local status, it = pcall( function()
						local r = '(' .. regex .. ')'
						local it = rex.gmatch( subject, r, options )
						return function()
							local m = { it() }
							if 0 == #m then
								return nil
							else
								for i = 1, #m do
									m[ i - 1 ] = m[ i ]
								end
								m[ #m ] = nil
								return m
							end
						end
					end)
					if status then
						return it
					else
						return nil
					end
				end,
				find   = function( subject, regex, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					local status, from, to, err = pcall( function()
					local m = { rex.find( subject, regex, 1, options ) }
						return m[1], m[2], nil
					end)
					if status then
						return from, to, nil
					else
						return nil, nil, from
					end
				end,
				sub   = function( subject, regex, replace, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					local status, newstr, n, err = pcall( function()
						local newstr, n, err = rex.gsub( subject, regex, replace, 1, options )
						return newstr, n, err
					end)
					if status then
						return newstr, n, nil
					else
						return nil, nil, newstr
					end
				end,
				gsub   = function( subject, regex, replace, options ) 
					if 'string' == type( options ) then options = options .. 'jo' else options = 'jo' end
					local status, newstr, n, err = pcall( function()
						local newstr, n, err = rex.gsub( subject, regex, replace, nil, options )
						return newstr, n, err
					end)
					if status then
						return newstr, n, nil
					else
						return nil, nil, newstr
					end
				end,
				log    = function( mes, pst, level )
					-- pst.lib is not available at this point
					-- copy in_array as local function here
					local in_array = function( needle, arr )
						if 'table' ~= type( arr ) then return false end
						needle = tostring( needle )
						for key, val in ipairs( arr ) do
							if val == needle then
								return key
							end
						end
						return false
					end
					if nil == level then level = 'ALERT' end
					level = tostring( level )
					mes = tostring( mes )
					local array = { 'STDERR', 'EMERG', 'ALERT', 'CRIT', 'ERR', 'WARN', 'NOTICE', 'INFO', 'DEBUG' }
					local key = in_array( level, array )
					if 'STDERR' == array[ key ] then key = false end
					if key then r[ string.lower( array[ key ] ) ]( r, mes ) end end,

--					TODO fcache
--				fcache = function( cmd, pst )
--
--					local do_not_cache          = tostring( ngx.var.do_not_cache )
--					if '0' ~= do_not_cache then return false end
--
--					if 'get' == cmd then 
--						if pst.body_cache then
--							ngx.arg[1] = pst.body_cache
--							ngx.arg[2] = true
--							return true
--						else
--							return false
--						end
--					end
--
--					local upstream_cache_status = ngx.var.upstream_cache_status
--					local fastcgi_cache_key     = ngx.var.fastcgi_cache_key
--					local wexal                 = ngx.shared.wexal
--					local fcache = tostring( pst.conf.lua.fcache.enable )
--					if '1' ~= fcache then fcache = false end
--					local exptime = tonumber( pst.conf.lua.fcache.exptime )
--					if 'number' ~= type( exptime ) then exptime = 60 end
--
--					if 'set' == cmd and fcache then
--						wexal:flush_expired( 10 )
--						wexal:set( pst.profile .. ':body:' .. fastcgi_cache_key, pst.body, exptime )
--						return
--					end
--					
--					if 'head' == cmd then
--						local bc = ':B=' .. tostring( pst.header['x-b-cache'] )
--						if fcache and 'HIT' == upstream_cache_status then
--							pst.body_cache = wexal:get( pst.profile .. ':body:' .. fastcgi_cache_key )
--							if pst.body_cache then
--								pst.header['x-b-cache'] = 'L=HIT' .. bc
--							else
--								pst.header['x-b-cache'] = 'L=MISS/CREATE' .. bc
--							end
--						elseif fcache then
--							pst.header['x-b-cache'] = 'L=CREATE' .. bc
--						else
--							pst.header['x-b-cache'] = 'L=BYPASS' .. bc
--						end
--					end
--				end,

			},
			profile     = profile,
			wexal_dir   = '/home/kusanagi/' .. profile .. '/wexal',
			phase       = httpd.get_phase(),
			header      = {},
			host        = r.hostname,
			https       = 'off',
			request_uri = r.uri,
			ext         =  '',
			is_user_logged_in = false,
			lyaml       = require 'lyaml',
		}
		if r.is_https then pst.https = 'on' end

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
		local _ = r.subprocess_env[ 'ORG_FLAG' ]
		if '/_wexal.dummy' == _ then _ = false else _ = true end
		if true == _ and false == pst.lib.in_array( 'apply_logged_in_user', pst.conf.options ) then
			return false
		end
		pst.is_user_logged_in = _
		local _ = r.subprocess_env[ 'EXT_NAME' ]
		if nil ~= _ then pst.ext = _ end

		httpd.ctx.pst = pst
	else

		-- Phase must be body_filter --
		if nil == httpd.ctx.pst then return false end
		local pst = httpd.ctx.pst
		pst.phase = httpd.get_phase()

		if nil == httpd.ctx.buf then httpd.ctx.buf = {} end
		local buf = httpd.ctx.buf

		coroutine.yield( '' )

		while bucket ~= nil do
			buf[ #buf + 1 ] = bucket
			coroutine.yield( '' )
		end
		pst.body = table.concat( buf )
		pst.fx.body = function( arg, pst )
			pst.body = arg
		end
	end
	-- finally --
	return httpd.ctx.pst
end

local main = function( r )
	-- init --
	local pst = httpd.ctx.init( r )
	if false == pst then return false end

	-- pre filter --
	if 'header_filter' == pst.phase then
--		pst.fx.fcache( 'head', pst )
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
					local retval = pst.lib.try( 
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
		for key, val in pairs( pst.header ) do
			r.headers_out[ key ] = val
		end
		httpd.set_phase( 'body_filter' )
	elseif 'body_filter' == pst.phase then
		pst.lib.wexal_pst_init( pst )
--		pst.fx.fcache( 'set', pst )
		coroutine.yield( pst.body )
	end
end

function bootstrap_httpd_handle( r )
	-- header_filter phase
	main( r )
	-- body_filter phase
	main( r )
end

