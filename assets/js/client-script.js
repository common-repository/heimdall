(function ($, hm, dt) {
    "use strict"

    function init() {

        const hooks = dt.ajaxhooks || [];
        const __DOC__ = $(document);
        const __WIN__ = $(window);

        hooks.forEach((hook) => {
            const data = hook.split("|");
            if (data.length <= 1) {
                return;
            }
            const params = data[1].split(':');
            const repeat = 'repeat' === (data[2] || '');
            if (typeof params[1] === 'undefined') {
                return;
            }
            if (params[0] === 'scrollTop') {
                __WIN__.on("scroll", function (event) {
                    const scrollTop = __WIN__.scrollTop();
                    if ((params[1] == 'end' && (scrollTop + __WIN__.height() == __DOC__.height())) ||
                        (params[1] != 'end' && scrollTop >= parseInt(params[1]))) {
                        post(hook);
                        if (!repeat) __WIN__.off(event);
                    }
                });
                __WIN__.trigger('scroll');
            }
            if (params[0] === 'document') {
                __DOC__.on(params[1], function (event) {
                    post(hook);
                    if (!repeat) __DOC__.off(event);
                });
                if (['ready', 'load'].indexOf(params[1]) > -1 && $.isReady) {
                    post(hook);
                    return;
                }
            }
            if (params[0] === 'inview') {
                const elements = $(params[1]);
                __WIN__.on("scroll", function (event) {
                    elements.each((i, e) => {
                        const element = $(e);
                        const isInview = Boolean(element.data('hmInview'));
                        const elementTop = element.offset().top;
                        const elementBottom = elementTop + element.outerHeight();
                        const viewportTop = __WIN__.scrollTop();
                        const viewportBottom = viewportTop + __WIN__.height();
                        if (elementBottom > viewportTop && elementTop < viewportBottom && !isInview) {
                            element.data('hmInview', true);
                            post(hook);
                            if (!repeat) __WIN__.off(event);
                        } else if (elementTop > viewportBottom || elementBottom < viewportTop) {
                            element.data('hmInview', false);
                        }
                    });
                });
            }
            if (params[0] === 'session') {
                let timeout = params[1];
                if (timeout.endsWith('s')){
                    timeout = timeout.replace("s", "000");
                }
                delayedPost(parseInt(timeout), hook, {} , repeat);
            }
        });

    }

    function delayedPost(timeout, action, data = {}, repeat = false) {
        if (timeout < 2000) timeout = 2000;
        setTimeout((a, d) => {
            post(a, d);
            if(repeat){
                delayedPost(timeout, action, data, repeat);
            }
        }, timeout, action, data);
    }

    function post(action, data = {}) {
        $.post(dt.ajaxurl, hm.merge({
            action: action,
            _wpnonce: dt.ajaxnonce
        }, dt.ajaxdata, data));
    }

    init();

})(jQuery, Heimdall, HeimdallData);