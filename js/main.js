require.config({
    baseUrl: "/wp-content/plugins/syn_wp_custom_banners/js/",
    paths: {
        "jquery": "./lib/jquery/dist/jquery",
      //  "handlebars": "./lib/handlebars/handlebars",
     //   "text": "./lib/requirejs-text/text"
	},
    shim: {
     //   handlebars: {
     //       exports: 'Handlebars'
     //   }
    }
});

requirejs(['uploader'], function(Uploader){

    $(document).ready(function(){
        window.uploader = new Uploader();
        window.uploader.start();
    });

});


