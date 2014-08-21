//Initial Upload Image

(function($) {
    
    var WP_images = function() {
        
        var _this = this,
            $currentHREF,
            $thumb,
            $textArea,
            $title,
            $inputLink,
            $pageLink,
            $saveButton;

        var toTitleCase = function(str) {
            return str.replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        }

        this.buttons = {};

        this.attachElements = function($container) {
        };

        this.uploadMedia = function() {

        };
    };

    $(document).ready(function(){
        var wp_images = new WP_images();
        wp_images.buttons.$save = $();
    });

})(jQuery);

var CustomBanners = function(){

	var _this = this;

  var
    $currentHREF,
    $thumb,
    $textArea,
    $title,
    $inputLink,
    $pageLink,
    $saveButton;

  this.media_uploader = function(metaboxID, nonce, postID ){
    var 
      that = this,
      $thumb =  jQuery( '#thumb-wrap-' + metaboxID ); 

    this.postID = postID;

    var setThumb = function(container, src, id){

      var img = jQuery('<img>')
        .attr({
          'src': src,
          'id': 'thumb-' + metaboxID,
          'data-id': id
        });

      var aTag = jQuery('<a>')
        .attr({
          'href': '#',
          'onClick': "customBanners.media_uploader('" + metaboxID + "', '" + nonce + "', " + postID + "); return false"
        });

      aTag.html('Choose another Image');

      container
        .empty()
        .html(img);
      container.append(aTag);
    }
    
    this.fileFrame = wp.media.frames.fileFrame = wp.media({
      title: toTitleCase(metaboxID.replace(/-/g, ' ')),
      button: {
        text: 'Insert Image',
      },
      multiple: false, // Set to true to allow multiple files to be selected
    });
    
    this.fileFrame.on( 'select', function() {
      var
        attachment = that.fileFrame.state().get('selection').first().toJSON(),
        postID     = that.postID;
        src        = attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url,
        id         = attachment.id;
      setThumb($thumb, src, id);
    });

    this.fileFrame.open();

  }

  this.setBanner = function( postID, metaboxID, nonce, bannerID, bannerLabel ) {

    var 
      win, 
      action,
      pageVal,
      inputVal,
      currentLinkVal,
      urls = {},

      $thumb           = jQuery( '#thumb-' + metaboxID ),
      $caption         = jQuery( '#caption-' + metaboxID ),
      $title           = jQuery( '#title-' + metaboxID ),
      $subtitle        = jQuery( '#subtitle-' + metaboxID ),
      $addCopy         = jQuery( '#additional-link-' + metaboxID ),
			$secCopy 				 = jQuery( '#second-additional-link-' + metaboxID ),
      $pageLink        = jQuery( '#page-' + metaboxID + ' option:selected' ),
      $saveButton      = jQuery( '#save-' + metaboxID + '-image' );

    urls['thumb']    = jQuery( '#thumb-url-' + metaboxID );
    urls['caption']  = jQuery( '#caption-url-' + metaboxID );
    urls['subtitle'] = jQuery( '#subtitle-url-' + metaboxID );
    urls['add']      = jQuery( '#additional-url-' + metaboxID );
		urls['sec']			 = jQuery( '#second-additional-url-' + metaboxID );

    jQuery.each(urls, function(ind, val){
      var
        url  = val.val() || '',
        proto = 'https://', 
        pos  = url.indexOf(proto),
        substr;

      //test for 'https'
      if ( pos == -1 ){
        proto = 'http://';
        pos = url.indexOf(proto);
      }

      url = url.replace(proto, '');

      //test for 'http'
      if ( pos == -1 || url.length == 0){
        urls[ind] = '';
        return;
      }

      urls[ind] = proto + url;

    });

    $saveButton.html('Saving...');
/*
    pageVal        = ( $pageLink.val() !== 'blank' ) ? true : false;
    inputVal       = ( $inputLink.val() && $inputLink.val() !== 'http://' ) ? true : false;
    currentLinkVal =  $currentHREF.val() ? true : false;

    if ( pageVal && inputVal && ! currentLinkVal ){
      $saveButton.html('Choose either a url or a page');
      return;
    }

    if ( inputVal ){
      link = $inputLink.val();
    }

    if ( pageVal ){
      link = $pageLink.val();
    }
    
    if ( currentLinkVal && ( ! pageVal && ! inputVal ) ) {
      link = $currentHREF.val();
    }
    
    if ( ! ( $title.val() && ( link.length || currentLinkVal )) ){
      $saveButton.html('Please Include All Fields');
      return;
    }  
*/
    
		if (!$thumb.length){
			saveState(jQuery('#' + metaboxID), 
				{
					button: $saveButton, 
					state: 'Please select an image'
				},
				{
					include: false
				}
				);
			return;
		}
		
		action = bannerID ? 'pc_update_banner_' : 'pc_insert_banner_';

    jQuery.post( ajaxurl, {
      action:      action + bannerLabel,  
      post_id:     postID,
      image_id:    $thumb.attr('data-id'),
      imglink:     urls['thumb'],
      caption:     $caption.val(),
      caplink:     urls['caption'],
      title:       $title.val(),
      subtitle:    $subtitle.val(),
      sublink:     urls['subtitle'],
      add_copy:    $addCopy.val(),
      addlink:     urls['add'],
			seclink:     urls['sec'],
			seccopy:     $secCopy.val(),
      banner_id:   bannerID,
      metabox:     metaboxID,
      _ajax_nonce: nonce,
      cookie: encodeURIComponent(document.cookie)
    }, 
    function( str ) {
      if( str.trim() === '0' ) {
        return setPostThumbnailL10n.error;
      } else {
				saveState(
					jQuery('#' + metaboxID), 
					{
						button: $saveButton, 
						state: 'Save'
					},
					{
						include: true,
						postId: parseInt(postID),
						bannerId: parseInt(str),
						metabox: metaboxID,
						nonce: nonce,
						bannerLabel: bannerLabel
					}
				);
      //  setBoxContent( str, metaboxID );
      }
    });
  }

  //Delete Button
  this.deleteBanner = function( postID, bannerID, metaboxID, nonce, bannerLabel ) {
    jQuery.post( ajaxurl, {
      action: 'pc_delete_banner_' + bannerLabel,  
      post_id: postID,
      metabox: metaboxID,
      banner_id: bannerID,
      _ajax_nonce: nonce,
      cookie: encodeURIComponent(document.cookie)
    }, function( str ) {
      if( str == '0' ) {
        return setPostThumbnailL10n.error;
      }
      else {
        setBoxContent( str, metaboxID );
      }   
    });
  }

	var saveState = function(metabox, saveConfig, deleteConfig){

		var submitBox = metabox.find('.submitbox'),
			deleteButton = metabox.find('.submitdelete');
			
		if (!deleteButton.length && deleteConfig.include){
			deleteButton = jQuery('<a>')
				.html('Remove Banner Image')
				.attr({
					'id': 'remove-' + deleteConfig.postId,
					'href': '#'
				})
				.addClass('submitdelete')
				.on('click', function(e){
					e.preventDefault();
					_this.deleteBanner(
						deleteConfig.postId,
						deleteConfig.bannerId,
						deleteConfig.metabox,
						deleteConfig.nonce,
						deleteConfig.bannerLabel
					);
			});
			submitBox.append(deleteButton);	
		}

		saveConfig.button.html(saveConfig.state)
			.data('bannerid', deleteConfig.bannerId);

	}

  var setBoxContent = function( contents, metaboxID ) {
    var 
      metabox = jQuery( '#' + metaboxID + ' .inside' );
    metabox.html( contents );
  }

  var setMetaValue = function( id, metaboxID ) {
    var field = jQuery('input[value=pc_' + metaboxID + '_id]', '#list-table');
    if ( field.size() > 0 ) {
      jQuery('#meta\\[' + field.attr('id').match(/[0-9]+/) + '\\]\\[value\\]').text( id );
    }    
  }

}

jQuery(document).ready(function(){
	var buttons = jQuery('.featured-image-buttons').find('.button-primary')
	buttons.each(function(){
		var _this 		= jQuery(this),
			postid 			=  _this.attr('data-postid'),
			metaboxid 	=  _this.attr('data-metaboxid'),
			nonce 			=  _this.attr('data-nonce'), 
			bannerid 		=  _this.attr('data-bannerid') || '',
			bannerlabel = _this.attr('data-bannerlabel');

		_this.data('postid', postid);
		_this.data('metaboxid', metaboxid);
		_this.data('nonce', nonce);
		_this.data('bannerid', bannerid);
		_this.data('bannerlabel', bannerlabel);

		_this.on('click', function(e){
			e.preventDefault();
			customBanners.setBanner(
				_this.data('postid'),
				_this.data('metaboxid'),
				_this.data('nonce'),
				_this.data('bannerid'),
				_this.data('bannerlabel')
			);
		});
	});
	customBanners = new CustomBanners();
});

