<?php defined('C5_EXECUTE') or die(_("Access Denied."));
  extract($vars);
?>

<?= $mode == 'live' ? '<script type="text/javascript" src="https://web.squarecdn.com/v1/square.js"></script>' : '<script type="text/javascript" src="https://sandbox.web.squarecdn.com/v1/square.js"></script>' ?>
<script>
	const appId = '<?= $publicAPIKey; ?>';
	const locationId = '<?= $locationKey; ?>';

	async function initializeCard(payments) {
		const card = await payments.card();
		await card.attach('#card-container');

		return card;
	}

	async function createPayment(token) {
		var nonceField = document.getElementById('card-nonce');
		nonceField.value = token;
		document.getElementById('store-checkout-form-group-payment').submit();
	}

	async function tokenize(paymentMethod) {
		const tokenResult = await paymentMethod.tokenize();
		if (tokenResult.status === 'OK') {
			return tokenResult.token;
		} else {
			let errorMessage = `Tokenization failed with status: ${tokenResult.status}`;
			if (tokenResult.errors) {
				errorMessage += ` and errors: ${JSON.stringify(
				  tokenResult.errors
				)}`;
			}

			throw new Error(errorMessage);
		}
	}

	// status is either SUCCESS or FAILURE;
	function displayPaymentResults(status) {
		const statusContainer = document.getElementById(
			'payment-status-container'
		);
		if (status === 'SUCCESS') {
			statusContainer.classList.remove('is-failure');
			statusContainer.classList.add('is-success');
		} else {
			statusContainer.classList.remove('is-success');
			statusContainer.classList.add('is-failure');
		}

		statusContainer.style.visibility = 'visible';
	}

	document.addEventListener('DOMContentLoaded', async function() {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}

		let payments;
		try {
			payments = window.Square.payments(appId, locationId);
		} catch {
			const statusContainer = document.getElementById(
				'payment-status-container'
			);
			statusContainer.className = 'missing-credentials';
			statusContainer.style.visibility = 'visible';
			return;
		}

		let card;
		try {
			card = await initializeCard(payments);
		} catch (e) {
			console.error('Initializing Card failed', e);
			return;
		}

		// Checkpoint 2.
		async function handlePaymentMethodSubmission(event, paymentMethod) {
			event.preventDefault();

			try {
				// disable the submit button as we await tokenization and make a payment request.
				cardButton.disabled = true;
				const token = await tokenize(paymentMethod);
				const paymentResults = await createPayment(token);
				displayPaymentResults('SUCCESS');

				console.debug('Payment Success', paymentResults);
			} catch (e) {
				cardButton.disabled = false;
				displayPaymentResults('FAILURE');
				console.error(e.message);
			}
		}

		const cardButton = $(".store-btn-complete-order").get(0);
		cardButton.addEventListener('click', async function(event) {
			await handlePaymentMethodSubmission(event, card);
		});
	});
</script>

<div class="form-group">
	<form id="payment-form"><div id="card-container"></div></form>
	<div id="payment-status-container"></div>
	<input type="hidden" id="card-nonce" name="nonce">
</div>