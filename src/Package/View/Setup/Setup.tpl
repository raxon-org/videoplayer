{{$register = Package.Raxon.Videoplayer:Init:register()}}
{{if(!is.empty($register))}}
{{Package.Raxon.Videoplayer:Import:role.system()}}
{{$flags = flags()}}
{{$options = options()}}
{{Package.Raxon.Videoplayer:Setup:install($flags, $options)}}
{{/if}}