/**************************** BILLPAY STUFF ********************************/ 

function showBillpayTermsPopup(termsUrl) {
	showBillpayGenericPopup('billpay-popup-terms', termsUrl, 620, 450, 50);
};

function showBillpayRateTermsPopup(url) {
	showBillpayGenericPopup('billpay-popup-rate-terms', url, 620, 450, 50);
};

function showBillpayPrivacyPopup(privacyUrl) {
	showBillpayGenericPopup('billpay-popup-privacy', privacyUrl, 620, 450, 0);
};

function showBillpayRatePrivacyPopup(url) {
	showBillpayGenericPopup('billpay-popup-rate-privacy', url, 620, 450, 50);
};

function showBillpayRateDetailsPopup(url) {
	showBillpayGenericPopup('billpay-popup-rate-details', url, 600, 410, 50);
};

function initBillpayGenericPopup(containerId, iframeId, width, height, marginTop) {
	if (document.getElementById(containerId) == null) {
		var div = document.createElement('div');
		div.id = containerId;
		div.style.display = 'none';
		div.style.backgroundColor = '#FFFFFF';
		div.style.border = 'solid 1px black';
		div.style.width = width + 'px';
		div.style.position = 'absolute';
		div.style.left = (document.documentElement.offsetWidth/2 - 250) + 'px';
		div.style.top = marginTop + 'px';
		div.style.zIndex = 9999;
		div.style.padding = '10px';
		
		var iframe = document.createElement('iframe');
		iframe.id = iframeId;
		iframe.frameBorder = 0;
		iframe.style.border = 0;
		iframe.style.width = div.style.width;
		iframe.style.height = height + 'px';
		iframe.style.overflowX = 'hidden';
		div.appendChild(iframe);
		
		var button = document.createElement('a');
		button.href = '#';
		button.innerHTML = 'Schlie&szlig;en';
		button.onclick = function() {
			document.getElementById(containerId).style.display = 'none';
			return false;
		};
		
		div.appendChild(button);
		document.body.insertBefore(div,null);
	}
};

function showBillpayGenericPopup(containerId, url, width, height, marginTop) {
	var iframeId = containerId + '-iframe';
	
	initBillpayGenericPopup(containerId, iframeId, width, height, marginTop);
	
	var scroll = self.pageYOffset||document.body.scrollTop||document.documentElement.scrollTop;
	var top = scroll + marginTop;

	var iframe = document.getElementById(iframeId);
	iframe.src = url;
	
	var container = document.getElementById(containerId);
	container.style.display = 'block';
	container.style.top = top + 'px';
};


document.observe("dom:loaded", function() {
	extendMagentoPayment();
});

var billpayPaymentMethods = ["billpay_rec", "billpay_elv", "billpay_rat"];

function extendMagentoPayment() {
	if (typeof(payment) == 'undefined' || typeof(payment.onSave) != "function") {
		return;
	}
	
	payment.onSave = payment.onSave.wrap(
		function(origMethod, transport) {
			
			if (!payment || typeof(payment.currentMethod) != "string") {
				origMethod(transport);
			}
			
			var cont = true;
	        if (billpayPaymentMethods.indexOf(payment.currentMethod) > -1) {
	            if (transport && transport.responseText){
	                try{
	                    response = eval('(' + transport.responseText + ')');
	                }
	                catch (e) {
	                    response = {};
	                }
	            }
	            if (response.error && response.fields && response.fields == "BILLPAY_DENIED") {
            		alert(response.error);
            		
            		payment.switchMethod(null);
            		
            		billpayPaymentMethods.each(function(s, index) {
            			if ($("p_method_" + s)) {
            				$("p_method_" + s).checked = false;
            				$("p_method_" + s).parentNode.hide();
            			}
	            		
            			if ($("payment_form_" + s)) {
            				$("payment_form_" + s).parentNode.hide();
            			}
	            		
            		});

                	cont = false;
            	}
	        }
	        
	        if (cont == true) {
	        	origMethod(transport);
	        }
		}
	);
};

function changeCustomerGroupValidator(customerGroup) {
	if(customerGroup == 1) {
		if ($("billpay_rec_day") != null) {
			$("billpay_rec_day").removeClassName('required-entry');
			$("billpay_rec_month").removeClassName('required-entry');
			$("billpay_rec_year").removeClassName('required-entry');
		}
		
		if ($("billpay-gender-select") != null) {
			$("billpay-gender-select").removeClassName('required-entry');
		}
	}
	else {
		if ($("billpay_rec_day") != null) {
			$("billpay_rec_day").addClassName('required-entry');
			$("billpay_rec_month").addClassName('required-entry');
			$("billpay_rec_year").addClassName('required-entry');
		}
		
		if ($("billpay-gender-select") != null) {
			$("billpay-gender-select").addClassName('required-entry');
		}
	}
	$("b2c").toggle();
	$("b2b").toggle();
};


function billpayCalculateRates(url, position, text) {
	
	if (position && text) {
		billpaySetStepInfo(position, text);
	}
	
	$('billpay_rates').update('');
	$('billpay_rat_step2').update('');
	$('billpay-rates-loading').show();

	var request = new Ajax.Request(
		url, {
			method:'post',
			onSuccess: function(transport) {
				$('billpay-rates-loading').hide();
				$('billpay_rates').update(billpayLoadResponse(transport));
			},
			//onFailure: billpayLoadingRatesError,
			parameters: Form.serialize(billpayGetForm())
		}
	);
};

function billpayGetForm() {
    var form = $('onestepcheckout-form');
    if (!form) {
        form = $('gcheckout-onepage-form');
    }
    if (!form) {
        form = $('co-payment-form');
    }
    if (!form) {
        form = $('firecheckout-form');
    }

    return form;
}

function billpayRateLoadStep2(url) {
	$('billpay_rat_step2').update('');
	$('billpay-step2-loading').show();
	if(typeof jQuery!='undefined') {
		jQuery('#billpay_rat_popup').css({height:"500px"});
	}
	var request = new Ajax.Request(
			url, {
			method:'post',
			onSuccess: function(transport) {
				$('billpay_rat_step2').update(billpayLoadResponse(transport));
				$('billpay_rat_step2').show();

				$('billpay-step2-loading').hide();
				$('billpay-calculation-buttons').hide();
			},
			//onFailure: billpayLoadingRatesError,
			parameters: Form.serialize(billpayGetForm())
		}
	);
}

function billpayRateSavePayment() {
	payment.save();
};

function billpaySetStepInfo(position, text) {
	if ($('billpay-step-info-pos')) {
		$('billpay-step-info-pos').update(position+".");
		$('billpay-step-info-box').update(text);
	}
};

function billpayLoadResponse(transport) {
	if (transport && transport.responseText) {
		try {
			return eval('(' + transport.responseText + ')');
		}
		catch (e) {
			return {};
		}
	}
};

var _bpyQry;
var _bpyQueryLoaded = false;
var _bpyQueryQueue = [];
var _bpyScriptTag;

/**
 * cross browser compatible event listener appending
 *
 * @param element
 * @param type
 * @param callback
 * @private
 */
function _bpyAddEvent(element, type, callback) {

    if (element.addEventListener) {
        element.addEventListener(type, callback, false);
    } else {
        switch(type) {
            case 'load':
                var done = false;
                element.onload = element.onreadystatechange = function() {
                    if (!done && (!this.readyState || this.readyState == 'loaded' || this.readyState == 'complete')) {
                        done = true; callback(); element.onload = element.onreadystatechange = null;
                    }
                };
                break;
            default:
                console.log('bpy not added event: ' + type);
                break;
        }
    }
}

/**
 * @param src
 * @param callback
 * @private
 */
function _bpyLoadScript(src, callback) {
    _bpyScriptTag = document.createElement('script');
    _bpyScriptTag.setAttribute('src', src);

    if (callback) {
        bpyQuery(callback);
    }

    _bpyAddEvent(_bpyScriptTag, 'load', function() {
        _bpyQry = jQuery.noConflict();
        _bpyQueryLoaded = true;
        if (_bpyQueryQueue.length > 0) {
            var _callback;
            while(_callback = _bpyQueryQueue.shift()) {
                _callback(_bpyQry);
            }
        }
    });

    var _scriptElements = document.getElementsByTagName('head')[0].getElementsByTagName('script');
    if (_scriptElements.length > 0) {
        document.getElementsByTagName('head')[0].insertBefore(_bpyScriptTag, document.getElementsByTagName('script')[0]);
    } else {
        document.getElementsByTagName('head')[0].appendChild(_bpyScriptTag);
    }
}

_bpyLoadScript('//code.jquery.com/jquery-1.10.2.min.js');

/**
 * executes a callback in the jquery context. parameter of the callback must accept the jquery object
 * @param callback
 */
function bpyQuery(callback) {
    if (_bpyQueryLoaded === true) {
        callback(_bpyQry);
    } else {
        _bpyQueryQueue.push(callback);
    }
}

/**
 * load jquery and use it instead of prototype
 */
bpyQuery(function($) {

    function bpyPopup(content) {
        return $(document.createElement('div'))
            .addClass('bpy-popup')
            .html(content)
            .hide()
            .appendTo($('body'))
            .fadeIn('fast');
    }

    function bpyExternalPopup(url, callback) {
        var containerClass = 'bpy-popup-' + url.replace(/[^\w\d]/g,'');
        var containerElement = $('.' + containerClass);
        if (containerElement.length > 0) {
            containerElement.remove();
        }
        var popupId = 'bpy_popup_' + Math.floor(Math.random() * 10001);
        var count = 0;
        while($('#' + popupId).length > 0 && count++ < 20) {
            popupId = 'bpy_popup_' + Math.floor(Math.random() * 10001);
        }
        var content = $(document.createElement("div"))
                .append(
                    $(document.createElement('div'))
                        .addClass('bpy-loader')
                )
                .append(
                    $(document.createElement('iframe'))
                        .attr('src', url)
                        .css('border', 'none')
                        .attr('frameborder', 0)
                        .attr('scrolling', 'auto')
                )
                .append(
                    $(document.createElement('a'))
                        .addClass('bpy-remove-aware bpy-popup-close')
                        .attr('data-remove-target', '#' + popupId)
                        .text('X')
                );

        content.find('iframe').bind('load', function(event) {
            content.find('.bpy-loader').hide();
            $(event.target).fadeIn('fast');
        });

        var element = bpyPopup(content)
            .addClass('bpy-external-popup')
            .addClass(containerClass)
            .attr('id', popupId);
        if (callback) {
            callback(element);
        }
    }

    function bpyShowHide(element) {
        if (element.is(':visible')) {
            element.hide();
        } else {
            element.show();
        }
    }

    function bpyHide(element) {
        if (element.is(':visible')) {
            element.fadeOut();
        }
    }

    function bpyShow(element) {
        if (element.is(':hidden')) {
            element.slideDown();
        }
    }

    function bpyRemove(element) {
        element.remove();
    }

    $(function() {
        $('body')
            .delegate('.bpy-btn-sepa-info-popup', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var callback;
                var eventTarget = $(event.target);
                if (eventTarget.attr('data-popup-target')) {
                    if (eventTarget.attr('data-popup-target') == 'auto') {
                        callback = function(element) {
                            element
                                .addClass('bpy-popup-sepa-converter')
                                .css({
                                    top: eventTarget.offset().top - 140
                                });
                        }
                    } else {
                        callback = function(element) {
                            eventTarget.addClass('bpy-popup-sepa-converter')
                                .parents(eventTarget.attr('data-popup-target'))
                                .css('position', 'relative')
                                .append(element);
                        }
                    }
                }
                bpyExternalPopup('https://www.billpay.de/api/sepa/converter', callback);
            })
            // prevent the opening of our api link -> quick'n dirty but very elegant i think
            .delegate('a[href^="https://www.billpay.de/api/ratenkauf/zahlungsbedingungen"]', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var element = bpyExternalPopup($(event.target).attr('href'));
                element.find('iframe').css({height: '580px', width: '580px'});
                element.css({height: '580px', width:  '580px'});
                element.css('margin-left', (element.width() / 2) * -1);

                $('html, body').animate({
                    scrollTop: element.offset().top
                }, 'slow');
            })
            .delegate('.bpy-btn-details', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var infoBox = $(event.target).parents('.bpy-terms-box-text').find('.bpy-additional-information-block');
                if (infoBox.is(':visible')) {
                    infoBox.slideUp('fast');
                } else {
                    infoBox.slideDown('slow');
                }
            })
            .delegate('.bpy-show-hide-aware', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var eventTarget = $(event.target);
                if (eventTarget.attr('data-show-hide-target')) {
                    eventTarget = $(eventTarget.attr('data-show-hide-target'));
                }
                bpyShowHide(eventTarget);
            })
            .delegate('.bpy-hide-aware', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var eventTarget = $(event.target);
                if (eventTarget.attr('data-hide-target')) {
                    eventTarget = $(eventTarget.attr('data-hide-target'));
                }
                bpyHide(eventTarget);
            })
            .delegate('.bpy-show-aware', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var eventTarget = $(event.target);
                if (eventTarget.attr('data-show-target')) {
                    eventTarget = $(eventTarget.attr('data-show-target'));
                }
                bpyShow(eventTarget);
            })
            .delegate('.bpy-remove-aware', 'click', function(event) {
                event.stopPropagation();
                event.preventDefault();

                var eventTarget = $(event.target);
                if (eventTarget.attr('data-remove-target')) {
                    eventTarget = $(eventTarget.attr('data-remove-target'));
                }
                bpyRemove(eventTarget);
            })
        ;
    })
});