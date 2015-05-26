(function($){
    window.ISM = window.ISM || {};

    ISM.slider = function(element, slides, options){
        this.el = $(element);
        this.slides = slides;
        this.init(options);
    }

    ISM.slider.prototype = {

        init : function(options){
            var self = this;

            self.settings = $.extend({
                prefix: 'ism',
                duration: 1000,
                width: 960,
                height:  400,
                frequency:  3000,
                controls: true,
                pagination: true,
                autoSlide: true,
                startIndx: 0,
                effect: 'fade',
                cl: {
                    conteiner :      $('.b-ism-slider-list', this.el),
                    item :           $('.b-ism-slider-item', this.el),
                    pagination :     $('.b-ism-slider-pagination-item', this.el),
                    leftControl :    $('.b-ism-slider-control-left', this.el),
                    rightControl :   $('.b-ism-slider-control-right', this.el),
                    preloader :      $('.b-ism-slider-preloader', this.el)
                }
            }, options || {});

            self.slength = self.settings.cl.item.length;

            if (self.settings.pagination){
                self.settings.cl.pagination.bind('click', $.proxy(self.paginationClickHandler, self)).parent().show();
            }

            $(window).bind('load', $.proxy(self.resize , self));
            $(window).bind('resize', $.proxy(self.resize , self));

            if (self.settings.controls){
                self.settings.cl.rightControl.show().bind('click', $.proxy(self.nextSlide, self));
                self.settings.cl.leftControl.show().bind('click', $.proxy(self.prevSlide, self));
            }
            this.getSlide(self.settings.startIndx);

            if (self.settings.autoSlide){
                self.timer = setInterval($.proxy(self.nextSlide, self), self.settings.frequency);
                self.el.bind('mouseover', function(){
                    clearInterval(self.timer);
                });
                self.el.bind('mouseout', function(){
                    self.timer = setInterval($.proxy(self.nextSlide, self), self.settings.frequency);
                });
            }
        },

        getSlide : function(page){
            if (typeof page !== 'number' || page > this.slength - 1 || page < 0) return;
            var curSlide = this.slides[page],
                self = this,
                callback = function(){
                    if (self.settings.pagination){
                        self.settings.cl.pagination.eq(page).addClass('current').siblings().removeClass('current');
                    }
                    self.settings.cl.item.siblings().removeClass('current').end().eq(page).addClass('current').fadeIn(self.settings.duration, function(){
                        $(this).siblings().hide(0);
                    })
                };

            if (!curSlide.loaded){
                var img = ISM.loadImage(curSlide.src,{
                    beforeLoad: function(img){
                        self.settings.cl.preloader.show();
                        //img.style.cssText = 'width:' + self.settings.width + 'px; height: ' + self.settings.height + 'px;'
                        img.style.cssText = 'width: 100%;height:auto;';
                        img.style.className += 'b-ism-slider-item-img ' + self.settings.prefix + '-item-img';
                        img.alt = curSlide.alt;
                    },
                    load: function(){
                        curSlide.loaded = true;
                        callback();
                        self.settings.cl.preloader.hide();
                    }
                });
                self.settings.cl.item.eq(page).find('.b-ism-slider-item-link').append(img);
            }else{
                callback();
            }
        },

        paginationClickHandler : function(e){
            if(this.settings.cl.item.is(':animated') || !e) return;
            var pageIndx = $(e.currentTarget).index();
            this.getSlide(pageIndx);
        },

        nextSlide : function(){
            if (this.settings.cl.item.is(':animated')) return;
            var current = this.settings.cl.item.filter('.current').index() + 1;
            if (current >= this.slength){
                current = 0;
            }
            this.getSlide(current);
        },

        prevSlide : function(){
            if (this.settings.cl.item.is(':animated')) return;
            var current = this.settings.cl.item.filter('.current').index() - 1;
            if (current < 0){
                current = this.slength - 1;
            }
            this.getSlide(current);
        },

        resize : function(){
            this.settings.cl.conteiner.height(  this.settings.cl.item.height() );
        }
    };
})(jQuery);