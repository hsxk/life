-- user directive sample --
local user = {}

user.header_filter_user = function( args, pst )
	pst.header.luajit = jit.version
end

user.body_filter_user = function( args, pst )
	local new_body = pst.fx.gsub( pst.body, 'if IE 7', 'IF IE SEVEN' )
	pst.fx.body( new_body, pst )
end

return user
