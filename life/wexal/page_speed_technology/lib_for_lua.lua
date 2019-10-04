local lib = { version = '1.1.8' }

lib.try = function( try, pst, level, catch )
	local status, result = pcall( try )
	if status then
		return result
	else
		if 'function' ~= type( catch ) then
			pst.fx.log( result, pst, level )
			return false
		else
			return catch( result, pst, level )
		end
	end
end

lib.join = function( sep, arr )
	if 'table' ~= type( arr ) then return false end
	local ret = ''
	local first = true
	for key, val in ipairs( arr ) do
		local str = tostring( val )
		if first then
			ret = str
			first = false
		else
			ret = ret .. sep .. str
		end
	end
	return ret
end

lib.split = function( args, pst )
	local str = args[1]
	local needle = args[2]
	str = str .. needle
	if false == str or false == needle then return {} end
	local ret = {}
	local i = 0
	local it = pst.fx.gmatch( str, '(.*?)' .. needle , 's' )
	while true do
		local m = it()
		if m then
			i = i + 1
			ret[ i ] = m[1]
		else
			break
		end
	end
	return ret
end

lib.in_array = function( needle, arr )
	if 'table' ~= type( arr ) then return false end
	needle = tostring( needle )
	for key, val in ipairs( arr ) do
		if val == needle then
			return key
		end
	end
	return false
end

lib.array_merge = function( def, arr )
	if 'table' ~= type( def ) then return false end
	if 'table' ~= type( arr ) then return false end
	local ret = {}
	for key, val in pairs( def ) do
		if nil ~= arr[ key ] then
			ret[ key ] = arr[ key ]
		else
			ret[ key ] = def[ key ]
		end
	end
	return ret
end

lib.merge = function( def, arr )
	if 'table' ~= type( def ) then return false end
	if 'table' ~= type( arr ) then return false end
	local ret = {}
	for key, val in ipairs( def ) do
		if nil ~= arr[ key ] then
			ret[ key ] = arr[ key ]
		else
			ret[ key ] = def[ key ]
		end
	end
	return ret
end

lib.file_exists = function( path )
	path = tostring( path )
	local f = io.open( path, 'r' )
	if f then
		f:close()
		return true
	else
		return false
	end
end

lib.file_get_contents = function( path )
	local f = io.open ( path , 'r' )
	local ret = f:read( '*all' )
	f:close()
	return ret
end

lib.preg_quote = function( str, pst )
	str = tostring( str )
	str = pst.fx.gsub( str, [=[(\\|\[|\^|\]|\-|[.+*?$(){}=!<>|:])]=], [[\$1]] )
	return str
end

lib.dollar_quote = function( str, pst )
	str = tostring( str )
	str = pst.fx.gsub( str, [[\$]], [[$$$$]] );
	return str
end

lib.is_apply = function( args, pst )
	local ret = false
	for key, val in ipairs( args.apply ) do
		if pst.lib.try ( function() return pst.fx.match( pst.request_uri, val )	end, pst ) then
			ret = true
			break
		end
	end

	for key, val in ipairs( args.exclude ) do
		if pst.lib.try ( function() return pst.fx.match( pst.request_uri, val ) end, pst ) then
			ret = false
			break
		end
	end

	return ret
end

lib.is_apply_script = function( args, pst )
	local ret = false
	if nil == args.apply_script then args.apply_script = '.' end
	if nil == args.src then return ret end
	if args.apply_script and pst.fx.match( args.src, args.apply_script ) then ret = true end
	if args.exclude_script and pst.fx.match( args.src, args.exclude_script ) then ret = false end
	return ret
end

lib.get_extension = function( path, pst )
	local m = pst.fx.match( path, [=[.*\.([^.]+)$]=] )
	if m then
		return m[1]
	else
		return false
	end
end

lib.get_resource = function( path, pst )
	local ret = false
	local ext = pst.lib.get_extension( path, pst )
	local m   = pst.fx.match( path, '^/_w(i|t|p|u)/' )
	if m then
		local regex, replacement
		if 'i' == m[1] then	regex, replacement = '^/_wi/', '/wp-includes/' end
		if 't' == m[1] then	regex, replacement = '^/_wt/', '/wp-content/themes/' end
		if 'p' == m[1] then	regex, replacement = '^/_wp/', '/wp-content/plugins/' end
		if 'u' == m[1] then	regex, replacement = '^/_wu/', '/wp-content/uploads/' end
		path = pst.fx.sub( path, regex, replacement )
	end

	local org = pst.conf.tdir .. path
	local opt = pst.wexal_dir .. '/optdir' .. path
	if pst.lib.in_array( ext, { 'js', 'css' } ) then
		opt = opt .. '.opt.' .. ext
	end

	if pst.lib.file_exists( opt ) then
		ret = pst.lib.file_get_contents( opt )
	elseif pst.lib.file_exists( org ) then
		ret = pst.lib.file_get_contents( org )
	end

	return ret
end

lib.wexal_pst_init = function( pst )
	if false == pst.lib.in_array( 'wp', pst.conf.options ) then
		if not pst.fx.match( pst.body, [[_wexal_pst=\w]] ) then
			local wexal = pst.lib.get_resource( '/wp-content/mu-plugins/pst/js/wexal_pst_init.js', pst )
			if false == wexal then return false end
			pst.lib.body_filter_replace_anything({
				'<head([^>]*?)>',
				'<head$1>' .. '\n' .. '<script>' .. wexal .. '</script>',
				1
			}, pst)
		end
	end
end

lib.header_filter_add = function( args, pst )
	for key, val in ipairs( args ) do
		val = tostring( val )
		local m = pst.fx.match( val, [=[^\s*([^\s]+)\s*:\s*(.*?)\s*$]=] )
		if m and pst.header[ m[1] ] then
			if 'table' ~= type( pst.header[ m[1] ] ) then
				pst.header[ m[1] ] = {
					pst.header[ m[1] ],
					m[2]
				}
			else
				local _ = pst.header[ m[1] ]
				table.insert( _, m[2] )
				pst.header[ m[1] ] = _
			end
		elseif m then
			pst.header[ m[1] ] = m[2]
		end 
	end
end

lib.header_filter_remove = function( args, pst )
	for key, val in ipairs( args ) do
		val = tostring( val )
		local m = pst.fx.match( val, [=[^\s*([^\s]+)\s*:\s*(.*?)\s*$]=] )
		if m and pst.header[ m[1] ] then
			if 'table' ~= type( pst.header[ m[1] ] ) then
				pst.header[ m[1] ] = nil
			else 
				key = pst.lib.in_array( m[2], pst.header[ m[1] ] )
				if key then
					local _ = pst.header[ m[1] ]
					table.remove( _, key )
					if 0 == #_ then
						pst.header[ m[1] ] = nil
					else 
						pst.header[ m[1] ] = _
					end
				end
			end
		elseif pst.header[ val ] then
			pst.header[ val ] = nil
		end
	end
end

lib.header_filter_replace = function( args, pst )
	if nil == args[1] or nil == args[2] then return false end
	pst.lib.header_filter_remove( { args[1] }, pst )
	pst.lib.header_filter_add( { args[2] }, pst )
end

lib.header_filter_server_push = function( args, pst )
	return pst.lib.preload( args, pst, 'header' )
end

lib.header_filter_set_cookie_for_cdn = function( args, pst )
	local def = {
		domain       = false,
		external_url = false,
	}
	args = pst.lib.array_merge( def, args )

	if 'header_filter' == pst.phase then
		local header = 'set-cookie: WEXAL_PST_EXT='  .. tostring( pst.ext ) .. '; path=/'
		local table = pst.conf.lua.header_filter;
		if 'string' == type( args.external_url ) then
			for k, v in ipairs( table ) do
				if 'set cookie for cdn' == v[ 'cmd' ] then
					pst.conf.lua.body_filter[ #pst.conf.lua.body_filter + 1 ] = v
					break
				end
			end
		end
		if 'string' == type( args.domain ) then
			header = header .. '; domain=' .. args.domain
			pst.lib.header_filter_add( { header }, pst )
		else
			pst.lib.header_filter_add( { header }, pst )
		end
	end

	if 'body_filter' == pst.phase then
		if 'string' == type( args.external_url ) then
			local delim, script
			if pst.fx.match( args.external_url,  [[\?]] ) then
				delim = '&'
			else
				delim = '?'
			end
			script = "<script async src='" .. args.external_url .. delim .. "WEXAL_PST_EXT="
				.. pst.ext .. "'></script>"
			pst.lib.body_filter_insert_script( { script, '</head>' }, pst )
		end
	end
end

lib.body_filter_preload = function( args, pst )
	return pst.lib.preload( args, pst, 'body' )
end

lib.preload = function( args, pst, pos )
	for k, url in ipairs( args ) do
		local media, ext, as, path, suffix, line, regex, m
		as = false
		media = ''
		suffix = ''
		if 'table' == type( url ) and 1 == #url then
			for key, val in pairs( url ) do
				media = " media='(" .. key .. ")'"
				if 'header' == pos then
					media = " media=(" .. key .. ")"
				end
				url = val
			end
		end
		if 'string' ~= type( url ) then return false end
		path = pst.fx.sub( url, [[\?.*$]], '' )
		ext = pst.lib.get_extension( path, pst )
		if pst.lib.in_array( ext, { 'woff2', 'woff', 'eot', 'ttf', 'otf' } ) then
			as = 'font'
			suffix = " type='font/" .. ext .. "' crossorigin"
		elseif pst.lib.in_array( ext, { 'png', 'jpeg', 'jpg', 'gif', 'webp', 'jp2', 'tiff', 'tif', 'svg', 'jxr', 'bmp' } ) then
			as = 'image'
		elseif 'js' == ext then
			as = 'script'
		elseif 'css' == ext then
			as = 'style'
		end
		
		if as and 'header' == pos then
			line = 'link: <' .. url .. '>; rel=preload; as=' .. as
			if "" ~= media then
				line = line .. ';' .. media
			end
			if "" ~= suffix then
				line = line .. '; crossorigin'
			end
			pst.lib.header_filter_add( { line }, pst )
		elseif as and 'body' == pos then
			regex = pst.lib.preg_quote( url, pst ) .. [=[(\?[^'"]*?)['"]]=]
			m = pst.fx.match( pst.body, regex )
			if m then
				url = url .. m[1]
			end
			line = "<link rel='preload' href='" .. url .. "' as='" .. as .. "'" .. media .. suffix .. ">"
			pst.lib.body_filter_insert_script( { line, '</head>' }, pst )
		end
	end
end

lib.body_filter_shorten_url = function( args, pst )
	return pst.lib.body_filter_wp_shorten_url( { general = true }, pst )
end

lib.body_filter_remove_js = function( args, pst )
	local _ = pst.body
	for key, val in ipairs( args ) do
		val = pst.lib.preg_quote( val, pst )
		_ = pst.fx.sub( _, [=[<script [^>]+?]=] .. val .. [=[[^>]+?></script>]=], '' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_remove_inline_js = function( args, pst )
	local _ = pst.body
	for key, val in ipairs( args ) do
		val = pst.lib.preg_quote( val, pst )
		_ = pst.fx.sub( _, [=[<script[^>]*?>[^<]*?]=] .. val .. [=[[^<]*?</script>]=], '' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_remove_link = function( args, pst )
	local _ = pst.body
	for key, val in ipairs( args ) do
		val = pst.lib.preg_quote( val, pst )
		_ = pst.fx.sub( _, [=[<link [^>]*?]=] .. val .. [=[[^>]*?>]=], '' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_remove_inline_css = function( args, pst )
	local _ = pst.body
	for key, val in ipairs( args ) do
		val = pst.lib.preg_quote( val, pst )
		_ = pst.fx.sub( _, [=[<style[^>]*?>[^<]*?]=] .. val .. [=[[^<]*?</style>]=], '' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_remove_meta = function( args, pst )
	local _ = pst.body
	for key, val in ipairs( args ) do
		val = pst.lib.preg_quote( val, pst )
		_ = pst.fx.sub( _, [=[<meta [^>]*?]=] .. val .. [=[[^>]*?>]=], '' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_insert_script = function( args, pst )
	local _ = pst.body
	local def = { '', '</head>' }
	args = pst.lib.merge( def, args )
	local script, pos = args[1], args[2]

	if '' == script or 'string' ~= type( script ) then return false end

	if 'string' == type( pos ) then
		if false == pst.lib.in_array( pos, { '<head>', '<body>', '</head>', '</body>' } ) then
			pos = '</head>'
		end
	else
		pos = '</head>'
	end

	if pst.lib.in_array( pos, { '</head>', '</body>' } ) then
		_ = pst.fx.sub( _, '(' .. pos .. ')', script .. '\n$1' )
	else
		pos = pst.fx.sub( pos, '>', [[[^>]*?>]] )
		_ = pst.fx.sub( _, '(' .. pos .. ')', '$1\n' .. script )
	end

	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_add_css = function( args, pst )
	local def = { false, false, false, false, true, false }
	if 1 == #args then
		args = { false, args[1] }
	end
	args = pst.lib.merge( def, args )
	local handle, src, pos, inline, ver, media = args[1], args[2], args[3], args[4], args[5], args[6]
	local script
	if false == inline or '' == inline then 
		if 'string' ~= type( handle ) then
			handle = ''
		else
			handle = " id='" .. handle .. "'"
		end

		if 'string' ~= type( src ) then return false end
		if 'string' ~= type( ver ) then
			if true == ver then
				ver = '?v=' .. pst.version
			else
				ver = ''
			end
		else
			ver = '?v=' .. ver
		end
		src = " href='" .. src .. ver .. "'"
	
		if 'string' ~= type( media ) then
			if false == media then
				media = ''
			else
				media = [[ media='all']]
			end
		end
	
		script = "<link rel='stylesheet'" .. handle .. src .. media .. ">"
	else
		script = pst.lib.get_resource( src, pst )
		if false == script then return false end
		script = "<style>" .. script .. "</style>"
	end

	pst.lib.body_filter_insert_script( { script, pos }, pst )
end

lib.body_filter_add_js = function( args, pst )
	local def = { false, false, false, false, true }
	if 1 == #args then
		args = { false, args[1] }
	end
	args = pst.lib.merge( def, args )
	local sync, src, pos, inline, ver = args[1], args[2], args[3], args[4], args[5]

	local script
	if false == inline or '' == inline then
		if 'string' ~= type( sync ) or false == pst.lib.in_array( sync, { 'defer', 'async' } ) then
			sync = ''
		else
			sync = " " .. sync
		end

		if 'string' ~= type( src ) then return false end
		if 'string' ~= type( ver ) then
			if true == ver then
				ver = '?v=' .. pst.version
			else
				ver = ''
			end
		else
			ver = '?v=' .. ver
		end
		src = " src='" .. src .. ver .. "'"
	
		script = "<script" .. sync .. src  .. "></script>"
	else
		script = pst.lib.get_resource( src, pst )
		if false == script then return false end
		script = "<script>" .. script .. "</script>"
	end

	pst.lib.body_filter_insert_script( { script, pos }, pst )
end

lib.body_filter_defer_external_js = function( args, pst )
	local _ = pst.body
	_ = pst.fx.gsub( _, [[<script src=]], [[<script type='text/javascript' src=]] )
	local it = pst.fx.gmatch( _, [=[<script type=('|")text/javascript('|") src=('|")([^>]+?)('|")]=] )
	if 'function' ~= type( it ) then return end
	while true do
		local m = it()
		if m then
			local src = m[4]
			if pst.lib.is_apply_script( { src = src, apply_script = args.apply_script, exclude_script = args.exclude_script }, pst ) then
				_ = pst.fx.sub( _, [[<script type=('|")text/javascript('|") src=]], '<script defer src=' ) 
			else
				_ = pst.fx.sub( _, [[<script type=('|")text/javascript('|") src=]], '<script src=' )
			end
		else
			break
		end
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_set_cookie_for_cdn = function( args, pst )
	return pst.lib.header_filter_set_cookie_for_cdn( args, pst )
end

lib.body_filter_replace = function( args, pst )
	local def = { '', '', -1  }
	if 'table' ~= type( args[1] ) then
		args = { args }
	end
	for k, arg in ipairs( args ) do
		arg = pst.lib.merge( def, arg )
		if ( arg[1] and arg[2] ) then
			local target  = pst.lib.preg_quote( arg[1], pst )
			local replace = pst.lib.dollar_quote( arg[2], pst )
			local limit = tonumber( arg[3] )
			if 'number' ~= type( limit ) then return end
			limit = math.floor( limit )
			if limit < 1 then
				pst.body = pst.fx.gsub( pst.body, target, replace )
			else
				for i = 1, limit do
					pst.body = pst.fx.sub( pst.body, target, replace )
				end
			end
			pst.fx.body( pst.body, pst )
		end
	end
end

lib.body_filter_replace_anything = function( args, pst )
	local def = { '', '', -1, '' }
	if 'table' ~= type( args[1] ) then
		args = { args }
	end
	for k, arg in ipairs( args ) do
		arg = pst.lib.merge( def, arg )
		if ( arg[1] and arg[2] ) then
			local regex    = arg[1]
			local replace  = arg[2]
			local limit    = tonumber( arg[3] )
			local modifier = arg[4]
			if 'number' ~= type( limit ) then return end
			limit = math.floor( limit )
			if limit < 1 then
				local retval = pst.lib.try (
					function()
						return pst.fx.gsub( pst.body, regex, replace, modifier )
					end, pst
				)
				if ( false ~= retval ) then
					pst.fx.body( retval, pst )
				end
			else
				for i = 1, limit do
					local retval = pst.lib.try (
						function()
							return pst.fx.sub( pst.body, regex, replace, modifier )
						end, pst
					)
					if ( false ~= retval ) then
						pst.fx.body( retval, pst )
					end
				end
			end
		end
	end
end

lib.body_filter_lazy_youtube = function( args, pst )
	local regex = [=[(<iframe[^>]*?width="(.*?)" height="(.*?)"[^>]*?)src(="https://www.youtube.com/embed/(.*)[^>]*?]=] .. [=[></iframe>)]=]
	local _ = pst.body
	local m = pst.fx.match( _, regex )
	if m then
		local def = {
			mobile = 'mq',
			pc     = 'hq',
			ratio  = '56.25'
		}
		args = pst.lib.array_merge( def, args )
		args.ratio = tostring( args.ratio )
		_ = pst.fx.gsub( _, regex, '<div class="youtube">$1data-src$4</div>' )
		local script = [[<script>_wexal_pst.lazy_youtube={'mobile':']] .. args.mobile
			.. [[', 'pc':']] .. args.pc
			.. [['}</script><script defer src='/wp-content/mu-plugins/pst/js/lazy_youtube.js?v=]]
			.. pst.version .. [['></script>"]] .. "\n"
			.. [[<style>.youtube img:hover { opacity: 0.5; } .youtube { position: relative; padding-bottom: ]]
			.. args.ratio .. [[%; height: 0; overflow: hidden; } .youtube iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }</style>]]
			.. "\n</body>"
		_ = pst.fx.sub( _, '</body>', script )		
		pst.fx.body( _, pst )
	end
end

lib.body_filter_engagement_delay = function( args, pst )
	local def = {
		score   = 250,
		pscore	= 10,
		high    = 'DOMContentLoaded',
		low     = 'load',
		delay   = 1000,
		ratio   = 4,
		inline  = false,
		debug   = false,
		scripts = {}
	}
	def['max-age'] = 7776000
	args = pst.lib.array_merge( def, args )
	local dbg = 'false'
	if args['debug'] then dbg = 'true' end
	local cjson = require 'cjson'
	local js = '/wp-content/mu-plugins/pst/js/engagement_delay.js'
	local script = "<script>!function(){var w=_wexal_pst.en;w.sc=" .. args.score .. ";w.hg='" .. args.high .. "';w.lw='" .. args.low .. "';w.dl=" .. args.delay .. ";w.rt=" .. args.ratio .. ";w.ma=" .. args['max-age'] .. ";w.ps=" .. args.pscore .. ";w.dbg=" .. dbg .. "}();</script>\n"
	local pos = '</body>'
	pst.lib.body_filter_insert_script( { script, pos }, pst )

	if false == args.inline or '' == args.inline then
		pst.lib.body_filter_add_js( { 'sync', js, pos }, pst )
	else
		script = "<script>" .. pst.lib.get_resource( js, pst )	.. "</script>"
		pst.lib.body_filter_insert_script( { script, pos }, pst )
	end

	def = {
		name    = '',
		type    = 'closure',
		pattern = '',
		args    = '',
		path    = '',
		needle  = '',
		query   = 'auto',
		sync    = 'sync',
		cmd     = {}
	}
	local count = 0
	for k, r in ipairs( args.scripts ) do
		r = pst.lib.array_merge( def, r )
		count = count + 1
		if '' == r.name then r.name = 'f' .. tostring( count ) end
		local _, js_path, str_path, needle, regex, typeregex, typeof, prefix, m, s, x, c
		_ = pst.body

		s = ''
		x = ''
		c = ',c:' .. tostring( count )
		if 'sync' == r.sync then s = ',s:1' end
		if pst.lib.in_array( r.type, { 'inline jsx', 'clousurex', 'cssx', 'jsx' } ) then x = ',x:1' end

		if 'inline js' == r.type or 'inline jsx' == r.type then
			needle = pst.lib.preg_quote( r.needle, pst )
			regex = [=[<script[^<]*?]=] .. [=[>]=] .. [=[([^<]*?]=] .. needle .. [=[.*?)</script>]=]
			replace = "<script>_wexal_pst.en.fn['" .. r.name .. "']={f:function(){$1},p:[]" .. s .. x .. c .. "}</script>"
			_ = pst.fx.sub( _, regex, replace, 's' )
			if _ then
				pst.fx.body( _, pst )
			end
		elseif 'closure' == r.type or 'closurex' == r.type then
			regex     = pst.lib.preg_quote( r.pattern, pst )
			typeregex = [=[^\s*(\(|\!|\+|\-|void|typeof)\s*function]=]
			m = pst.fx.match( r.pattern, typeregex )
			if m then
				typeof = m[1]

				r.pattern = pst.fx.sub( r.pattern, typeregex, 'function' )
				_ = pst.fx.sub( _, regex, "_wexal_pst.en.fn['" .. r.name .. "']={f:" .. r.pattern )
				regex = pst.lib.preg_quote( r.args, pst )
				m = pst.fx.match( _, [[\s*\(\s*]] .. regex .. [[.*\)\s?\);]] )
				if '(' == typeof and nil == m then
					prefix = [[\)]]
				else
					prefix = '';
				end
				_ = pst.fx.sub( _, prefix .. [[\s*\(\s*(]] .. regex .. [[).*?;]], ',p:[$1]' .. s .. x .. c .. '};' )
				if 'table' == type( r.cmd ) then
					local j = 0
					for k, row in ipairs( r.cmd ) do
						local func
						if 'string' == type( row['function'] ) then
							func = row['function']
						else
							func = r.name
						end
						if 'string' == type( row.args ) then
							regex = [[(;|\s|^)]] .. pst.lib.preg_quote( func, pst ) .. [[\s*\(\s*]] ..
								pst.lib.preg_quote( row.args, pst ) .. [[\s*\)\s*;]]
							if 0 == j then
								_ = pst.fx.sub( _, regex, "$1_wexal_pst.en.fn['" .. 
									r.name .. "'].cmd=[['" .. func .. "'," .. row.args .. "]];" )
								j = j + 1
							else
								_ = pst.fx.sub( _, regex, "$1_wexal_pst.en.fn['" .. 
									r.name .. "'].cmd.push( ['" .. func .. "'," .. row.args .. "] );" )
							end
						end
					end
				end
				if _ then
					pst.fx.body( _, pst )
				end
			end
		elseif ( 'css' == r.type or 'cssx' == r.type ) and '' ~= r.path then
			js_path  = r.path
			str_path = pst.lib.preg_quote( r.path, pst )
			regex    = [=[<link[^>]*?rel\s*=\s*('|")stylesheet[^>]*?href\s*=\s*('|")]=]
				.. str_path .. [=[(\?[^'">]+)?('|")[^>]*?]=] .. [=[>]=]
			m = pst.fx.match( _, regex )
			if m then
				if 'auto' == r.query then
					js_path = js_path .. m[3]
				end
				js_path = cjson.encode( js_path )
				_ = pst.fx.sub( _, regex, "<script>_wexal_pst.en.css['" .. r.name .. "']={url:" .. js_path .. s .. x .. c .. "}</script>" )
				if _ then
					pst.fx.body( _, pst )
				end
			end
		elseif ( 'js'  == r.type or 'jsx' == r.type ) and '' ~= r.path then
			js_path  = r.path
			str_path = pst.lib.preg_quote( r.path, pst )
			regex    = [=[<script[^>]*?src\s*=\s*('|")]=]
				.. str_path .. [=[(\?[^'">]+)?('|")[^>]*?]=] .. [=[></script>]=]
			m = pst.fx.match( _, regex )
			if m then
				if 'auto' == r.query then
					js_path = js_path .. m[2]
				end
				js_path = cjson.encode( js_path )
				_ = pst.fx.sub( _, regex, "<script>_wexal_pst.en.js['" .. r.name .. "']={url:" .. js_path .. s .. x .. c .. "}</script>" )
				if _ then
					pst.fx.body( _, pst )
				end
			end
		end
	end

end

lib.header_filter_wp_remove_header = function( args, pst )
	local protocol = 'https'
	if pst.https ~= 'on' then protocol = 'http' end
	local def = {
		pings_open              = 'x-pingback',
		rest_output_link_header = 'link: <' .. protocol .. '://' .. pst.host .. '/wp-json/>; rel="https://api.w.org/"',
		wp_shortlink_header     = 'link: <' .. protocol .. '://' .. pst.host .. '/>; rel=shortlink',
	}
	args = lib.array_merge( def, args )
	for k, v in pairs( args ) do
		if 'string' == type( v ) and '' ~= v and 5 < #v then
			pst.lib.header_filter_remove( { v }, pst )
		end
	end
end

lib.body_filter_wp_shorten_url = function( args, pst )
	local protocol = 'http'
	if 'on' == pst.https then
		protocol = 'https'
	end
	local host = pst.lib.preg_quote( pst.host, pst )
	local _ = pst.body
	_ = pst.fx.gsub( _, [[ (href|src)=("|')]] .. protocol .. '://' .. host .. '/', [[ $1=$2/]] )
	if true ~= args.general then
		_ = pst.fx.gsub( _, [[=("|')/wp-content/uploads/]], '=$1/_wu/' )
		_ = pst.fx.gsub( _, [[=("|')/wp-content/themes/]], '=$1/_wt/' )
		_ = pst.fx.gsub( _, [[=("|')/wp-content/plugins/]], '=$1/_wp/' )
		_ = pst.fx.gsub( _, [[=("|')/wp-includes/]], '=$1/_wi/' )
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_wp_remove_emoji = function( args, pst )
	pst.lib.body_filter_remove_inline_js( { [=[everything:!0,everythingExceptFlag:!0},i=0;i<j.length;i++)]=] }, pst )
	pst.lib.body_filter_remove_inline_css( { 'img.emoji {' }, pst )
end

lib.body_filter_wp_opt_genericons = function( args, pst )
	pst.lib.body_filter_remove_link( { 'genericons.css' }, pst )
	pst.lib.body_filter_add_css( { 'opt-genericons', '/wp-content/mu-plugins/pst/css/genericons.css', '</body>' }, pst )
end

lib.body_filter_wp_remove_meta = function( args, pst )
	local _ = pst.body
	local def = {
		feed_links       = [=[; Feed" href=]=],
		feed_links_extra = [=[; Comments Feed" href=]=],
		rsd_link         = [=[="EditURI" type="application/rsd+xml" title="RSD"]=],
		wlwmanifest_link = [=[="wlwmanifest" type="application/wlwmanifest+xml"]=],
		rest_output_link_wp_head = [=[rel='https://api.w.org/' href=]=],
		wp_oembed_add_discovery_links = [=[rel="alternate" type="(application|text)/(xml|json)\+oembed" href=]=],
		wp_shortlink_wp_head = [=[rel='shortlink' href=]=],
		rel_canonical    = [=[rel="canonical" href="]=],
		wp_generator     = [=[name="generator" content="]=],
	}
	args = lib.array_merge( def, args )
	for k, v in pairs( args ) do
		if 'string' == type( v ) and '' ~= v and 5 < #v then
			if 'wp_oembed_add_discovery_links' == k then
				_ = pst.fx.sub( _, [=[<link [^>]*?]=] .. v .. [=[[^>]*?>]=], '' )
				_ = pst.fx.sub( _, [=[<link [^>]*?]=] .. v .. [=[[^>]*?>]=], '' )
			else
				v = pst.lib.preg_quote( v, pst )
				_ = pst.fx.sub( _, [=[<(link|meta) [^>]*?]=] .. v .. [=[[^>]*?>]=], '' )
			end
		end
	end
	if _ then
		pst.fx.body( _, pst )
	end
end

lib.body_filter_wp_remove_wpcf7 = function( args, pst )
	pst.lib.body_filter_remove_link( { [[rel='stylesheet' id='contact-form-7-css']] }, pst )
	pst.lib.body_filter_remove_inline_js( { '<![CDATA[ */\nvar wpcf7 = {' }, pst )
	pst.lib.body_filter_remove_js( { '/contact-form-7/includes/js/scripts.js' }, pst )
end

return lib
