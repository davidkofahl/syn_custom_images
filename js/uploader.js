(function ($) {
    'use strict';

    var WP_images = function() {
        
        var _this = this,
            $formWrap,
            $forms,
            $currentForm,
            buttons = {};

        var fileFrame = {};

        var toTitleCase = function(str) {
            return str.replace(/\w\S*/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        };

        var addFormHandler = function(evt) {
            evt.preventDefault();
            var count = $forms.length + 1;
            _this.attachForm({banners: [{count: count}]});
        };

        var bannerSaveHandler = function(evt) {
            evt.preventDefault();
            var $this = $(this),
                $form = $this.closest('.banner-form');

            _this.uploadBanner($form, $this);
        };

        var bannerUpdateHandler = function(evt) {
            evt.preventDefault();
            var $this = $(this),
                $form = $this.closest('.banner-form');

            _this.uploadBanner($form, $this);
        };

        var addImageHandler = function(evt) {
            evt.preventDefault();
            var $this = $(this),
                $form = $this.closest('.banner-form');

            $currentForm = $form;
            
            _this.openUploader($form);
        };

        var bannerDeleteHandler = function(evt) {
            evt.preventDefault();
            
            var $this = $(this),
                $form = $this.closest('.banner-form'),
                data = {
                    'banner_id': $form.data().bannerId
                };
            
            _this.delete(data).then(function(response) {
                $form.remove(); 
            });
        };
            
        var setThumb = function($form, data){
            var $addImage = $form.find('.add-image-button'),
                $thumbWrap = $form.find('.thumb-wrap'),
                $bannerThumb = $form.find('.banner-thumb'),
                $thumb = $bannerThumb.length ? $bannerThumb : $('<img>');
            
            $thumb.attr('src', data.src).addClass('banner-thumb');
            $addImage.html('Choose another Image');
            $thumbWrap.empty().append($thumb);
        };

        this.baseData = {};
            
        this.uploadBanner = function($form, $button) {
            var $image = $form.find('.banner-thumb'),
                $imageTitle = $form.find('.image-title'),
                $textArea = $form.find('.caption'),
                $title = $form.find('.title'),
                $delete = $form.find('.delete-banner'),
                type = $button.data().type,
                data = {
                    'image_src': $image.attr('src'),
                    'caption': $textArea.val(),
                    'title': $title.val(),
                    'menu_order': $forms.index($form),
                };

            if (data.image_src && data.image_src.length) {
                data.banner_id = $form.data().bannerId ? $form.data().bannerId : null; 
                $button.html('Saving...');

                _this[type](data).then(function(response) {
                    var response = {
                        banners: [$.parseJSON(response)]
                    };
                    _this.attachForm(response, $form, true);
                });
            } else {
                $imageTitle.html('Image is required<sup>*</sup>').css('color', 'red');
                $button.html('Image is required');
            }
                
        };

        this.openUploader = function($form){
            fileFrame.open();
        };
        
        this.attachForm = function(data, $form, replace) {

            var html = this.formTemplate(data);

            if (replace) {
                $form.replaceWith(html); 
            } else {
                $formWrap.append(html);
            }

            $forms = $('.banner-form');
            buttons.$save = $('.banner-save');
            
        };

        this.save = function(formData) {
            var data = $.extend(true, formData, _this.baseData);
            data.action = 'syn_insert_banner'; 
            return _this.post(data);
        };

        this.update = function(formData) {
            var data = $.extend(true, formData, _this.baseData);
            data.action = 'syn_update_banner';
            return _this.post(data);
        };

        this.delete = function(formData) {
            var data = $.extend(true, formData, _this.baseData);
            data.action = 'syn_delete_banner';
            return _this.post(data);
        };

        this.post = function(data) {
           return $.post(ajaxurl, data);
        };

        this.start = function() {

            this.formTemplate = Handlebars.compile(window.uploaderData.template);
            $formWrap = $('#custom-banner-form-wrap');
            
            this.baseData = {
                parent_id: $formWrap.data().postId,
                nonce: $formWrap.data().metaNonce,
                cookie: encodeURIComponent(document.cookie),
            };

            var banners = $.parseJSON(window.uploaderData.banners);
            banners = banners.length ? banners : [{menu_order: 1}];

            this.attachForm({banners: banners});

            buttons.$addForm = $('#add-new-banner');
            buttons.$addForm.on('click', addFormHandler);

            $formWrap.on('click', '.banner-save', bannerSaveHandler);
            $formWrap.on('click', '.banner-update', bannerUpdateHandler);
            $formWrap.on('click', '.add-image-button', addImageHandler);
            $formWrap.on('click', '.delete-banner', bannerDeleteHandler);

            fileFrame = wp.media.frames.fileFrame = wp.media({
                title: "Select Image",
                button: {
                    text: 'Insert Image',
                },
                multiple: false,
            });

            fileFrame.on( 'select', function() {
                var data = {},
                    attachment = fileFrame.state().get('selection').first().toJSON(),
                    src = attachment.sizes.medium ? attachment.sizes.full.url : attachment.url,
                    id = attachment.id;

                data.attachment = attachment;
                data.src = src;
                data.id = id;
                setThumb($currentForm, data);
            });
            
        };
    };

    $(document).ready(function() {
        var wp_images = new WP_images();
        wp_images.start();
    });
    
})(jQuery);
