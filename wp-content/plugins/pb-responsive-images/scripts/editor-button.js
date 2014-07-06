(function() {
	tinymce.create('tinymce.plugins.pbEditImage', {
		url: '',

		init: function(ed, url) {
			var	t = this,
				ed = tinyMCE.activeEditor,
				DOM = tinymce.DOM,
				editButton;

			t.url = url;

			// Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
			ed.addCommand('PBDisableImage', function() {
				var el = ed.selection.getNode(), cls = ed.dom.getAttrib(el, 'class');

				if (cls.indexOf('mceItem') != -1 ||
					cls.indexOf('wpGallery') != -1 ||
					el.nodeName != 'IMG')
					return;

				if(!cls.match(/(\s|^)+non-responsive(\s|$)+/))
					cls += ' non-responsive';
				else
					cls = cls.replace(/(\s|^)non-responsive(\s|$)/,' ');
				
				ed.dom.setAttrib(el,'class',cls);
			});

			editButton = DOM.add('wp_editbtns', 'img', {
				src : url.replace(/scripts$/,'') + 'images/editor-icon.png',
				id : 'wp_editimgbtn',
				width : '24',
				height : '24',
				title : 'Toggle Responsive Sizing'
			});

			tinymce.dom.Event.add(editButton, 'mousedown', function(e) {
				var ed = tinyMCE.activeEditor;
				ed.execCommand("PBDisableImage");
			});


			// show editimage buttons
			ed.onMouseDown.add(function(ed, e) {
				var target = e.target;

				if ( target.nodeName != 'IMG' ) {
					if ( target.firstChild && target.firstChild.nodeName == 'IMG' && target.childNodes.length == 1 )
						target = target.firstChild;
					else return;
				}

				t.populateState(ed,editButton,target);
			});
		},

		populateState : function(ed,editButton,target) {
			var classes = ed.dom.getAttrib(target, 'class'),
				enabled = !classes.match(/(\s|^)+non-responsive(\s|$)+/);

			if ( classes.indexOf('mceItem') != -1 ) return;

			ed.dom.setAttrib(editButton,'title',(enabled ? 'Disable' : 'Enable') + ' Responsive Sizing');
			ed.dom.setAttrib(editButton,'src',this.url.replace(/scripts$/,'') + 'images/editor-icon' + (enabled ? '' : '-disabled') + '.png');
		},

		getInfo : function() {
			return {
				longname : 'Toggle Responsive Image',
				author : 'Phenomblue',
				authorurl : 'http://phenomblue.com',
				infourl : '',
				version : "1.0"
			};
		}
	});

	tinymce.PluginManager.add('pbeditimage', tinymce.plugins.pbEditImage);
})();