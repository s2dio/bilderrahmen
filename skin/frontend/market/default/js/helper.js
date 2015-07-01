var docElem = window.document.documentElement;

(function ($j) {

    function getViewportW() {
        var client = docElem['clientWidth'],
            inner = window['innerWidth'];

        if (client < inner)
            return inner;
        else
            return client;
    };

    function getViewportH() {
        var client = docElem['clientHeight'],
            inner = window['innerHeight'];

        if (client < inner)
            return inner;
        else
            return client;
    };

    $j(window).on('load', function() {
        var menuBar = $j('#nav'),
            vertnav = $j('#vertnav'),
            search = $j('#search_mini_form'),
            headerBar= $j('#header-search');
        vertnav.clone().appendTo(menuBar);
        search.clone().appendTo(headerBar);
    });


})(jQuery);