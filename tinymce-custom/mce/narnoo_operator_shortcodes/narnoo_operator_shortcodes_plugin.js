(function() {
	tinymce.create('tinymce.plugins.NarnooOperatorShortcodes', {
		init : function(ed, url) {
			ed.addCommand('mceNarnooOperatorShortcodes', function() {
				ed.windowManager.open({
					file : url + '/dialog.htm',
					width : 350 + parseInt(ed.getLang('highlight.delta_width', 0)),
					height : 350 + parseInt(ed.getLang('highlight.delta_height', 0)),
					inline : 1,
					title : 'Narnoo Shortcode',
					resizable: true
				}, {
					plugin_url : url
				});
			});

			// Register Narnoo Shortcode button
			ed.addButton('narnoo_operator_shortcodes', {
				title : 'Narnoo Shortcode',
				cmd : 'mceNarnooOperatorShortcodes',
				image : url + '/img/icon-20.png'
			});
		},
		getInfo : function() {
			return {
				longname : 'Narnoo Shortcode',
				author : 'Tarmizi Ahmad',
				authorurl : 'http://hirewordpressplugindeveloper.com/',
				infourl : 'http://narnoo.com/',
				version : "1.0"
			};
		}
	});

	// Register plugin
	tinymce.PluginManager.add('narnoo_operator_shortcodes', tinymce.plugins.NarnooOperatorShortcodes);
})();