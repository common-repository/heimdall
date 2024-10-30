(function ($, hm, dt) {
    "use strict"

    const __DOC__ = $(document);

    function init() {
        initDashboard();
        initUI();
    }

    function initUI() {

        $(".hmd-tabs").each((i, e) => {
            $(e).tabs({
                activate: (event, ui) => {
                    __DOC__.trigger("heimdall.dashboard.tabs-activate", [i, e, ui]);
                },
                create: function (event, ui) {
                    __DOC__.trigger("heimdall.dashboard.tabs-create", [i, e, ui]);
                },
                show: false,
                hide: false
            })
        });

        $('.hmd-tag-editor').each((i, e) => {
            $(e).tagEditor({
                placeholder: $(e).data('placeholder') || '',
                forceLowercase: false
            });
        });
    }

    function initDashboard() {
        const storageKey = "dashboard";

        __DOC__.on("heimdall.dashboard.tabs-activate", (event, index, element, ui) => {
            if (element.id == 'statisticTabs') {
                hm.updateLocalStorage(storageKey, { lastTab: ui.newTab.index() });
            }
        });

        __DOC__.on("heimdall.dashboard.tabs-create", (event, index, element, ui) => {
            if (element.id == 'statisticTabs') {
                const dashData = hm.readFromLocalStorage(storageKey);
                const activeTab = dashData?.data?.lastTab || 0;
                $(element).tabs("option", "active", activeTab);
            }
        });
    }

    __DOC__.ready(init);

})(jQuery, Heimdall, HeimdallData);