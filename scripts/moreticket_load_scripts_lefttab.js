
/**
 *  Load plugin scripts on page start
 */
(function ($) {
    $.fn.moreticket_load_scripts_lefttab = function () {

        init();
        var object = this;

        // Start the plugin
        function init() {
            var path = 'plugins/moreticket/';
            var url = window.location.href.replace(/front\/.*/, path);
            if (window.location.href.indexOf('plugins') > 0) {
                url = window.location.href.replace(/plugins\/.*/, path);
            }

            if (location.pathname.indexOf('front/ticket.form.php') > 0) {
                // Launched on each complete Ajax load
                $(document).ajaxComplete(function (event, xhr, option) {
                    setTimeout(function () {
                        // Get the right tab
                        if (option.url != undefined
                            && (object.urlParam(option.url, '_itemtype') == 'Ticket'
                            && object.urlParam(option.url, '_glpi_tab') == 'Ticket$main')
                            && option.url.indexOf("ajax/common.tabs.php") != -1) {
                            $.ajax({
                                url: url + 'ajax/loadscripts.php',
                                type: "POST",
                                dataType: "html",
                                data: 'action=load',
                                success: function (response, opts) {
                                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                                    while (scripts = scriptsFinder.exec(response)) {
                                        eval(scripts[1]);
                                    }
                                }
                            });
                        } else if (option.url != undefined
                            && option.url.indexOf("ajax/timeline.php") > 0 ){
                            $.ajax({
                                url: url + 'ajax/loadscripts.php',
                                type: "POST",
                                dataType: "html",
                                data: 'action=load',
                                success: function (response, opts) {
                                    var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                                    while (scripts = scriptsFinder.exec(response)) {
                                        eval(scripts[1]);
                                    }
                                }
                            });
                        }


                    }, 100);
                }, this);
            }

            if (location.pathname.indexOf('helpdesk.public.php') > 0
                || location.pathname.indexOf('tracking.injector.php') > 0 ) {
                $.ajax({
                    url: url + 'ajax/loadscripts.php',
                    type: "POST",
                    dataType: "html",
                    data: 'action=load',
                    success: function (response, opts) {
                        var scripts, scriptsFinder = /<script[^>]*>([\s\S]+?)<\/script>/gi;
                        while (scripts = scriptsFinder.exec(response)) {
                            eval(scripts[1]);
                        }
                    }
                });
            }

        }

        /**
         * Get url parameter
         *
         * @param string url
         * @param string name
         */
        this.urlParam = function (url, name) {
            var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(url);
            if (results == null || results == undefined) {
                return  0;
            }

            return results[1];
        };

        return this;
    }
}(jQuery));

$(document).moreticket_load_scripts_lefttab();