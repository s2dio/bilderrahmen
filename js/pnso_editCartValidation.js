document.observe("dom:loaded", function() {
	var cartItems = {};
	var cartItemAmounts = {};
	var cartItemPrices = {};
	var cartItemUnitPrices = {};
	var cartItemErrors = new Array();
	var currentTotalAmount = 0.0;
	var basicCartAmount = 0.0;

	var errorOccured = false;
	var rowError = false;
	var editing = false;

	$$('.cartRow').each(function(element) {
		var parentRowId = element.getAttribute('id');
		var inputName = parentRowId;
		var quantity = element.down('.quantityCell input').value;
		var price = element.down('.priceCell span').innerHTML;
		var unitPrice = element.down('.unitPriceCell input').value;

		var basicQuantity = parseFloat(element.down('.lastQuantity span').innerHTML.replace(',', '.'));
		var basicPrice = parseFloat(element.down('.lastPrice span').innerHTML.replace(',', '.'));

		basicCartAmount += basicPrice * basicQuantity;

		cartItems[inputName] = quantity;
		cartItemAmounts[inputName] = quantity;
		cartItemPrices[inputName] = price;
		cartItemUnitPrices[inputName] = unitPrice;
		currentTotalAmount += parseFloat(price.replace(',', '.'));
	});

	$('basicTotalPrice').update(basicCartAmount.toFixed(2).toString().replace('.', ','));
	$('currentTotalPrice').update(currentTotalAmount.toFixed(2).toString().replace('.', ','));

	setCurrentPriceStyle();
	toggleErrors();

	var newPrice = 0.0;

	$$('.cartRow input').each(function(element) {
		element.observe('keyup', function(event) {
			recalculatePrice(this);
			updateCart(this);
		});
	});

	function updateCart() {
		newPrice = updateTotalGross();

		if (parseFloat(newPrice) < parseFloat(0.0)) {
			errorOccured = 1;
		}

		if (checkQuantities() < 0) {
			errorOccured = 1;
		}

		setCurrentPriceStyle();
		toggleErrors();
	}

	function checkQuantities() {
		var quantities = 0;
		Object.keys(cartItemAmounts).each(function(element) {
			if (cartItemAmounts[element] > 0)
				quantities++;
		});

		return quantities;
	}

	function checkItemCount() {
		var count = 0;
		Object.keys(cartItemAmounts).each(function(element) {
			if (cartItemAmounts[element] != null)
				count++;
		});

		return count;
	}

	function recalculatePrice(input) {
		var inputName = input.name;
		var value = input.value == '' ? 0 : input.value;
		var parentRow = input.up('tr');
		var quantity = Math.abs(parseInt(parentRow.down('.quantityCell input').value.replace(',', '.')));
		var unitPrice = parseFloat(parentRow.down('.unitPriceCell input').value.replace(',', '.'));
		var price = unitPrice * quantity;

		if (isNaN(quantity) || isNaN(unitPrice)) {
			price = 0.0;
			rowError = true;
			//input.up().setAttribute('style', '');
			input.up(1).setStyle({
				backgroundColor : 'rgb(250, 235, 231)',
				border : ' 1px solid rgb(250, 235, 231)',
				color : 'rgb(223, 40, 10)'
			});
		} else {
			rowError = false;
			input.up(1).setAttribute('style', '');
		}

		//update current prices
		var currentPrice = price.toFixed(2).toString().replace('.', ',');
		parentRow.down('.priceCell span').update(currentPrice);
		$('currentTotalPrice').update(recalculateTotal().toFixed(2).toString().replace('.', ','));

		setCurrentPriceStyle();
		
		//block other inputs, when one changed
		if (parseInt(quantity) != parseInt(cartItemAmounts[parentRow.id])) {
			// block unit price
			parentRow.down('.unitPriceCell input').setAttribute('disabled', 'disabled');
			parentRow.down('.unitPriceCell input').addClassName('disabled');
			parentRow.down('.unitPriceCell input').setStyle({
				outline : 'none'
			});
		} else {
			parentRow.down('.unitPriceCell input').removeAttribute('disabled');
			parentRow.down('.unitPriceCell input').removeClassName('disabled');
			parentRow.down('.unitPriceCell input').setStyle({
				outline : 'standart'
			});
		}

		if (parseFloat(unitPrice) != parseFloat(cartItemUnitPrices[parentRow.id])) {
			//console.log('quantity readonly');
			// block quantity
			parentRow.down('.quantityCell input').setAttribute('disabled', 'disabled');
			parentRow.down('.quantityCell input').addClassName('disabled');
			parentRow.down('.quantityCell input').setStyle({
				outline : 'none'
			});
		} else {
			//console.log('quantity writeable');
			parentRow.down('.quantityCell input').removeAttribute('disabled');
			parentRow.down('.quantityCell input').removeClassName('disabled');
			parentRow.down('.quantityCell input').setStyle({
				outline : 'standart'
			});
		}
	}


	function setCurrentPriceStyle(){
		if (basicCartAmount == currentTotalAmount) {
			$('currentPrice').setStyle({
				color : '#ccc'
			})
		} else {
			$('currentPrice').setStyle({
				color: 'rgb(47, 47, 47)'
			})
		}
	}

	function updateTotalGross() {
		var total = recalculateTotal();
		currentTotalAmount = total;
		checkErrors();

		return total.toFixed(2);
	}

	function recalculateTotal() {
		var total = 0.0;
		$$('.priceCell span').each(function(element) {
			total += parseFloat(element.innerHTML.replace(',', '.'));
		});

		return total;
	}

	function checkErrors() {
		if (currentTotalAmount > basicCartAmount || currentTotalAmount < 0) {
			errorOccured = true;
		} else {
			errorOccured = false;
		}
	}

	function toggleErrors() {
		if (errorOccured) {
			toggleErrorMessages(1)
			$('saveButton').setAttribute('disabled', 'disabled');
			return true;
		} else {
			toggleErrorMessages(0)
			$('saveButton').removeAttribute('disabled');
			return false;
		}
	}

	//shows or hides error messages and sets current total price field class ('error')
	function toggleErrorMessages(show) {
		if (show) {
			$('errorMessages').show();
			$('errorMessageCancel').hide();
			$('currentPrice').addClassName('error');
			
			if (currentTotalAmount > basicCartAmount) {
				$('errorMessageAmountHigh').show();
				$('errorMessageAmountLow').hide();
			} else if (currentTotalAmount < 0) {
				$('errorMessageAmountHigh').hide();
				$('errorMessageAmountLow').show();
			}
		} else {
			$('errorMessages').hide();
			$('errorMessageAmountHigh').hide();
			$('errorMessageAmountLow').hide();

			if(currentTotalAmount == 0) {
				$('errorMessages').show();
				$('errorMessageCancel').show();
			}
			
			$('currentPrice').removeClassName('error');
		}
	}

}); 