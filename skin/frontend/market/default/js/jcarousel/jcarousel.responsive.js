(function ($) {
    $( document ).ready( function() {
        $('.jcarousel').each(function () {
            var self = $(this);

            self.on('jcarousel:reload jcarousel:create', function () {
                var width = self.outerWidth();
                if (width >= 600) {
                    width = width / 4;
                } else if (width >= 450) {
                    width = width / 3;
                }
                else if (width >= 350) {
                    width = width / 2;
                }
                self.jcarousel('items').css('width', width + 'px');
            })
                .jcarousel({
                    wrap: 'circular'
                })
                .jcarouselAutoscroll({
                    interval: 3000,
                    target: '+=1',
                    autostart: false
                });

        });


        var sliderlProductImage = $('.jcarousel-more-images');
        sliderlProductImage
            .on('jcarousel:reload jcarousel:create', function () {
                var width = sliderlProductImage.outerWidth();
                if (width >= 500) {
                    width = width / 5;
                } else if (width >= 400) {
                    width = width / 4;
                }
                else if (width >= 300) {
                    width = width / 3;
                }
                sliderlProductImage.jcarousel('items').css('width', width + 'px');
            })
            .jcarousel({
                wrap: 'circular'
            })
            .jcarouselAutoscroll({
                interval: 3000,
                target: '+=1',
                autostart: true
            });
        $('.jcarousel-control-prev')
            .jcarouselControl({
                target: '-=1'
            });

        $('.jcarousel-control-next')
            .jcarouselControl({
                target: '+=1'
            });
        $('.jcarousel-last-image')
            .jcarouselControl({
                target: '-=1'
            });

        $('.jcarousel-next-image')
            .jcarouselControl({
                target: '+=1'
            });
    })

})(jQuery);


