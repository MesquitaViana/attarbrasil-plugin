(function () {
    'use strict';

    function initializeSection(section) {
        if (section.dataset.absTabsReady === 'true') return;

        var tabs = Array.prototype.slice.call(section.querySelectorAll('[data-abs-tab]'));
        var panels = Array.prototype.slice.call(section.querySelectorAll('[data-abs-panel]'));
        if (tabs.length < 2 || panels.length < 2) return;

        section.dataset.absTabsReady = 'true';

        function activate(name, focus) {
            tabs.forEach(function (tab) {
                var active = tab.dataset.absTab === name;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
                tab.tabIndex = active ? 0 : -1;
                if (active && focus) tab.focus();
            });

            panels.forEach(function (panel) {
                var active = panel.dataset.absPanel === name;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });

			var dynamicLink = section.querySelector('[data-abs-tab-link]');
			if (dynamicLink) {
				var nextUrl = dynamicLink.getAttribute('data-url-' + name);
				if (nextUrl) dynamicLink.setAttribute('href', nextUrl);
			}
        }

        tabs.forEach(function (tab, index) {
            tab.addEventListener('click', function () {
                activate(tab.dataset.absTab, false);
            });
            tab.addEventListener('keydown', function (event) {
                if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight' && event.key !== 'Home' && event.key !== 'End') return;
                event.preventDefault();
                var target = index;
                if (event.key === 'ArrowRight') target = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') target = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') target = 0;
                if (event.key === 'End') target = tabs.length - 1;
                activate(tabs[target].dataset.absTab, true);
            });
        });
    }

	function money(value) {
		return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0);
	}

	function initializeGallery(gallery) {
		if (gallery.dataset.absReady === 'true') return;
		gallery.dataset.absReady = 'true';
		var slides = Array.prototype.slice.call(gallery.querySelectorAll('[data-abs-gallery-slide]'));
		var thumbs = Array.prototype.slice.call(gallery.querySelectorAll('[data-abs-gallery-thumb]'));
		var current = 0, touchStart = 0;
		function show(index) {
			if (!slides.length) return;
			current = (index + slides.length) % slides.length;
			slides.forEach(function (slide, i) { slide.hidden = i !== current; slide.classList.toggle('is-active', i === current); });
			thumbs.forEach(function (thumb, i) { thumb.classList.toggle('is-active', i === current); thumb.setAttribute('aria-selected', i === current ? 'true' : 'false'); });
			var count = gallery.querySelector('[data-abs-gallery-current]'); if (count) count.textContent = current + 1;
			if (thumbs[current]) thumbs[current].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
		}
		thumbs.forEach(function (thumb) { thumb.addEventListener('click', function () { show(Number(thumb.dataset.absGalleryThumb)); }); });
		var prev = gallery.querySelector('[data-abs-gallery-prev]'), next = gallery.querySelector('[data-abs-gallery-next]');
		if (prev) prev.addEventListener('click', function () { show(current - 1); });
		if (next) next.addEventListener('click', function () { show(current + 1); });
		var stage = gallery.querySelector('.attar-pdp__stage');
		if (stage) {
			stage.addEventListener('touchstart', function (event) { touchStart = event.changedTouches[0].clientX; }, { passive: true });
			stage.addEventListener('touchend', function (event) { var delta = event.changedTouches[0].clientX - touchStart; if (Math.abs(delta) > 45) show(current + (delta < 0 ? 1 : -1)); }, { passive: true });
		}
	}

	function initializePdp(pdp) {
		if (pdp.dataset.absReady === 'true') return;
		pdp.dataset.absReady = 'true';
		var price = Number(pdp.dataset.price) || 0;
		var regular = Number(pdp.dataset.regularPrice) || price;
		var subtotal = Number(pdp.dataset.cartSubtotal) || 0;
		var threshold = Number(pdp.dataset.freeShipping) || 700;
		var discount = Number(pdp.dataset.pixDiscount) || 0;
		var installments = Number(pdp.dataset.installments) || 1;
		var autoVariationId = Number(pdp.dataset.autoVariationId) || 0;
		var selectedVariation = autoVariationId;
		var variableProduct = pdp.dataset.variable === 'yes';
		var quantityInput = pdp.querySelector('.attar-pdp__cart input.qty');
		var primaryImage = pdp.querySelector('[data-abs-gallery-slide="0"] img');
		var originalImage = primaryImage ? { src: primaryImage.src, srcset: primaryImage.srcset } : null;
		var initialPriceNode = pdp.querySelector('[data-abs-price]');
		var initialPriceHtml = initialPriceNode ? initialPriceNode.innerHTML : '';
		var bundleMainImages = Array.prototype.slice.call(pdp.querySelectorAll('[data-abs-bundle-main-image]'));
		var bundleMainOriginals = bundleMainImages.map(function (image) { return { image: image, src: image.src, srcset: image.srcset }; });

		function selectedVariationLabel(form) {
			return Array.prototype.slice.call(form.querySelectorAll('select[name^="attribute_"]')).map(function (select) {
				var option = select.options[select.selectedIndex]; return option && option.value ? option.text.trim() : '';
			}).filter(Boolean).join(' · ');
		}
		function updateBundleMain(variation, label) {
			pdp.querySelectorAll('[data-abs-bundle-main-label]').forEach(function (node) { node.textContent = label || ''; });
			bundleMainImages.forEach(function (image) { if (variation && variation.image && variation.image.src) { image.src = variation.image.src; image.srcset = variation.image.srcset || ''; } });
			if (!variation) bundleMainOriginals.forEach(function (item) { item.image.src = item.src; item.image.srcset = item.srcset; });
		}

		function quantity() { return quantityInput ? Math.max(1, Number(quantityInput.value) || 1) : 1; }
		function updateCommercial() {
			var priceNode = pdp.querySelector('[data-abs-price]'), regularNode = pdp.querySelector('[data-abs-regular]'), savingNode = pdp.querySelector('[data-abs-saving]'), discountNode = pdp.querySelector('[data-abs-discount]');
			var awaitingVariation = variableProduct && !selectedVariation;
			if (priceNode) { if (awaitingVariation) priceNode.innerHTML = initialPriceHtml; else priceNode.textContent = money(price); }
			if (regularNode) { regularNode.textContent = money(regular); regularNode.hidden = awaitingVariation || regular <= price; }
			var saving = Math.max(0, regular - price), percent = regular > price ? Math.round(saving / regular * 100) : 0;
			if (discountNode) { discountNode.textContent = percent + '% OFF'; discountNode.hidden = awaitingVariation || !percent; }
			if (savingNode) { savingNode.innerHTML = 'Você economiza <strong>' + money(saving) + '</strong>'; savingNode.hidden = awaitingVariation || !saving; }
			var pix = pdp.querySelector('[data-abs-pix]'), installment = pdp.querySelector('[data-abs-installment]');
			if (pix) pix.textContent = money(price * (1 - discount / 100));
			if (installment) installment.textContent = installments + 'x de ' + money(price / installments);
			var projected = subtotal + price * quantity(), missing = Math.max(0, threshold - projected), progress = Math.min(100, projected / threshold * 100);
			var bar = pdp.querySelector('[data-abs-shipping-bar]'), message = pdp.querySelector('[data-abs-shipping-message]');
			if (bar) bar.style.width = progress + '%';
			if (message) message.innerHTML = missing > 0 ? 'Faltam <strong>' + money(missing) + '</strong> para você ganhar frete grátis.' : '<strong>Você atingiu o valor para frete grátis.</strong>';
			pdp.querySelectorAll('[data-abs-bundle-slide]').forEach(function (slide) {
				var total = price * quantity() + Number(slide.dataset.companionPrice || 0), totalNode = slide.querySelector('[data-abs-bundle-total]'), parcelNode = slide.querySelector('[data-abs-bundle-installment]');
				if (totalNode) totalNode.textContent = money(total);
				if (parcelNode) parcelNode.textContent = installments + 'x de ' + money(total / installments) + ' sem juros';
				var buy = slide.querySelector('[data-abs-bundle-buy]');
				if (buy && buy.dataset.loading !== 'yes') buy.disabled = slide.dataset.ready !== 'yes' || awaitingVariation;
			});
		}

		if (quantityInput) {
			var quantityWrap = quantityInput.closest('.quantity');
			if (quantityWrap) {
				var minus = quantityWrap.querySelector('[data-abs-qty-minus], button.minus, .minus'), plus = quantityWrap.querySelector('[data-abs-qty-plus], button.plus, .plus');
				var createdMinus = !minus, createdPlus = !plus;
				if (!minus) { minus = document.createElement('button'); minus.type = 'button'; minus.textContent = '−'; quantityWrap.insertBefore(minus, quantityInput); }
				if (!plus) { plus = document.createElement('button'); plus.type = 'button'; plus.textContent = '+'; quantityWrap.appendChild(plus); }
				minus.dataset.absQtyMinus = ''; plus.dataset.absQtyPlus = '';
				minus.setAttribute('aria-label', 'Diminuir quantidade'); plus.setAttribute('aria-label', 'Aumentar quantidade');
				function adjustQuantity(direction) { var step = Number(quantityInput.step) || 1, min = Number(quantityInput.min) || 1, max = quantityInput.max ? Number(quantityInput.max) : Infinity, value = Math.min(max, Math.max(min, (Number(quantityInput.value) || min) + direction * step)); quantityInput.value = value; quantityInput.dispatchEvent(new Event('change', { bubbles: true })); }
				if (createdMinus) minus.addEventListener('click', function () { adjustQuantity(-1); });
				if (createdPlus) plus.addEventListener('click', function () { adjustQuantity(1); });
			}
			quantityInput.addEventListener('change', updateCommercial); quantityInput.addEventListener('input', updateCommercial);
		}

		if (window.jQuery) {
			var variationForm = window.jQuery(pdp).find('form.variations_form');
			variationForm.on('found_variation', function (event, variation) {
				selectedVariation = Number(variation.variation_id) || 0; price = Number(variation.display_price) || 0; regular = Number(variation.display_regular_price) || price;
				if (variation.image && variation.image.src && primaryImage) { primaryImage.src = variation.image.src; primaryImage.srcset = variation.image.srcset || ''; }
				updateBundleMain(variation, selectedVariationLabel(variationForm[0]));
				updateCommercial();
			}).on('reset_data hide_variation', function () { selectedVariation = 0; price = Number(pdp.dataset.price) || 0; regular = Number(pdp.dataset.regularPrice) || price; if (primaryImage && originalImage) { primaryImage.src = originalImage.src; primaryImage.srcset = originalImage.srcset; } updateBundleMain(null, ''); updateCommercial(); });
			if (autoVariationId && variationForm.length) {
				window.setTimeout(function () {
					var variations = variationForm.data('product_variations') || [];
					var automatic = variations.filter(function (variation) { return Number(variation.variation_id) === autoVariationId; })[0];
					if (!automatic) return;
					Object.keys(automatic.attributes || {}).forEach(function (name) { variationForm.find('select[name="' + name + '"]').val(automatic.attributes[name]).trigger('change'); });
					variationForm.find('input.variation_id').val(autoVariationId);
					variationForm.trigger('check_variations');
					variationForm.trigger('found_variation', [automatic]);
				}, 0);
			}
		}

		var cartFeedback = pdp.querySelector('[data-abs-cart-feedback]');
		function normalizeCartFeedback() {
			if (!cartFeedback) return;
			var links = pdp.querySelectorAll('.attar-pdp__cart a.added_to_cart, .attar-pdp__cart a.wc-forward, .attar-pdp__summary a.added_to_cart.wc-forward, .attar-pdp__summary a.button.wc-forward');
			links.forEach(function (link) {
				if (link.closest('[data-abs-bundle]') || link.parentNode === cartFeedback) return;
				link.classList.add('attar-pdp__cart-link');
				cartFeedback.appendChild(link);
			});
			cartFeedback.hidden = !cartFeedback.querySelector('a');
		}
		normalizeCartFeedback();
		if (cartFeedback && window.MutationObserver) {
			var cartObserver = new MutationObserver(normalizeCartFeedback);
			cartObserver.observe(pdp, { childList: true, subtree: true });
		}
		if (window.jQuery) window.jQuery(document.body).on('added_to_cart.absPdp', function () { window.setTimeout(normalizeCartFeedback, 0); });

		var bundle = pdp.querySelector('[data-abs-bundle]');
		if (bundle) {
			var track = bundle.querySelector('[data-abs-bundle-track]'), slides = Array.prototype.slice.call(bundle.querySelectorAll('[data-abs-bundle-slide]')), current = 0;
			var bundleTouchStart = 0;
			function showBundle(index) { if (!slides.length) return; current = (index + slides.length) % slides.length; if (track) track.style.transform = 'translateX(-' + current * 100 + '%)'; bundle.querySelectorAll('[data-abs-bundle-dot]').forEach(function (dot, i) { dot.classList.toggle('is-active', i === current); if (i === current) dot.setAttribute('aria-current', 'true'); else dot.removeAttribute('aria-current'); }); }
			var prev = bundle.querySelector('[data-abs-bundle-prev]'), next = bundle.querySelector('[data-abs-bundle-next]');
			if (prev) prev.addEventListener('click', function () { showBundle(current - 1); }); if (next) next.addEventListener('click', function () { showBundle(current + 1); });
			bundle.querySelectorAll('[data-abs-bundle-dot]').forEach(function (dot) { dot.addEventListener('click', function () { showBundle(Number(dot.dataset.absBundleDot)); }); });
			var bundleViewport = bundle.querySelector('.attar-pdp__bundle-viewport');
			if (bundleViewport && slides.length > 1) { bundleViewport.addEventListener('touchstart', function (event) { bundleTouchStart = event.changedTouches[0].clientX; }, { passive: true }); bundleViewport.addEventListener('touchend', function (event) { var delta = event.changedTouches[0].clientX - bundleTouchStart; if (Math.abs(delta) > 45) showBundle(current + (delta < 0 ? 1 : -1)); }, { passive: true }); }
			bundle.querySelectorAll('[data-abs-bundle-buy]').forEach(function (button) {
				button.addEventListener('click', function () {
					var slide = button.closest('[data-abs-bundle-slide]'), status = slide.querySelector('[data-abs-bundle-status]'), variableForm = pdp.querySelector('form.variations_form');
					if (variableForm && !selectedVariation) { status.textContent = 'Selecione uma variação do produto principal.'; return; }
					button.disabled = true; button.dataset.loading = 'yes'; button.textContent = 'Adicionando...'; status.textContent = '';
					var data = new FormData(); data.append('action', 'abs_add_bundle'); data.append('nonce', pdp.dataset.bundleNonce); data.append('main_id', pdp.dataset.productId); data.append('main_variation_id', selectedVariation); data.append('companion_id', slide.dataset.companionId); data.append('companion_variation_id', slide.dataset.companionVariationId || 0); data.append('quantity', quantity());
					fetch(pdp.dataset.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: data }).then(function (response) { return response.json(); }).then(function (response) {
						if (!response.success) throw new Error(response.data && response.data.message ? response.data.message : 'Não foi possível adicionar.');
						status.innerHTML = response.data.message + ' <a href="' + response.data.cart_url + '">Ver carrinho</a>';
						if (window.jQuery) window.jQuery(document.body).trigger('wc_fragment_refresh');
					}).catch(function (error) { status.textContent = error.message; }).finally(function () { button.dataset.loading = 'no'; button.disabled = slide.dataset.ready !== 'yes' || (variableProduct && !selectedVariation); button.textContent = 'Comprar os 2 itens'; });
				});
			});
			showBundle(0);
		}
		updateCommercial();
	}

    function initialize() {
        document.querySelectorAll('[data-abs-tabs]').forEach(initializeSection);
		document.querySelectorAll('[data-abs-gallery]').forEach(initializeGallery);
		document.querySelectorAll('[data-abs-pdp]').forEach(initializePdp);

		document.querySelectorAll('[data-abs-description]').forEach(function (wrap) {
			if (wrap.dataset.absReady === 'true') return;
			wrap.dataset.absReady = 'true';
			var content = wrap.querySelector('[data-abs-description-content]');
			var button = wrap.querySelector('[data-abs-description-toggle]');
			if (!content || !button) return;
			if (content.scrollHeight <= content.clientHeight + 8) { button.hidden = true; content.classList.remove('is-collapsed'); return; }
			button.addEventListener('click', function () {
				var expanded = button.getAttribute('aria-expanded') === 'true';
				button.setAttribute('aria-expanded', expanded ? 'false' : 'true');
				button.firstChild.nodeValue = expanded ? 'Saiba mais ' : 'Mostrar menos ';
				content.classList.toggle('is-collapsed', expanded);
			});
		});

		document.querySelectorAll('[data-abs-filter-toggle]').forEach(function (button) {
			if (button.dataset.absReady === 'true') return;
			button.dataset.absReady = 'true';
			var filters = document.getElementById(button.getAttribute('aria-controls'));
			if (!filters) return;
			button.addEventListener('click', function () {
				var open = filters.classList.toggle('is-open');
				button.setAttribute('aria-expanded', open ? 'true' : 'false');
				document.body.classList.toggle('abs-lock-scroll', open);
			});
		});

		document.querySelectorAll('[data-abs-filter-close]').forEach(function (close) {
			if (close.dataset.absReady === 'true') return;
			close.dataset.absReady = 'true';
			close.addEventListener('click', function () {
				var filters = document.querySelector('[data-abs-filters]');
				var toggle = document.querySelector('[data-abs-filter-toggle]');
				if (filters) filters.classList.remove('is-open');
				if (toggle) toggle.setAttribute('aria-expanded', 'false');
				document.body.classList.remove('abs-lock-scroll');
			});
		});

		document.querySelectorAll('[data-abs-load-more]').forEach(function (button) {
			if (button.dataset.absReady === 'true') return;
			button.dataset.absReady = 'true';
			button.dataset.initialHref = button.getAttribute('href') || '';
			button.dataset.initialCurrent = button.dataset.current || '1';
			button.addEventListener('click', function (event) {
				if (!window.fetch || button.classList.contains('is-loading')) return;
				event.preventDefault();
				var url = button.getAttribute('href');
				var container = button.closest('[data-abs-catalog-products]');
				var grid = container ? container.querySelector('.attar-products-grid') : null;
				var status = button.parentNode.querySelector('.attar-load-more__status');
				if (!url || !grid) return;
				button.classList.add('is-loading');
				button.setAttribute('aria-busy', 'true');
				button.querySelector('span').textContent = 'Carregando...';
				fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(function (response) { if (!response.ok) throw new Error('HTTP ' + response.status); return response.text(); })
					.then(function (html) {
						var documentNext = new DOMParser().parseFromString(html, 'text/html');
						var nextContainer = documentNext.querySelector('[data-abs-catalog-products]');
						var cards = nextContainer ? nextContainer.querySelectorAll('.attar-products-grid > .attar-product-card') : [];
						var nextButton = nextContainer ? nextContainer.querySelector('[data-abs-load-more]') : null;
						cards.forEach(function (card) { var imported = document.importNode(card, true); imported.classList.add('abs-loaded-product'); grid.appendChild(imported); });
						var lessButton = button.parentNode.querySelector('[data-abs-show-less]');
						if (lessButton) lessButton.hidden = false;
						if (status) status.textContent = cards.length + (cards.length === 1 ? ' produto carregado.' : ' produtos carregados.');
						if (nextButton) {
							button.href = nextButton.href;
							button.dataset.current = nextButton.dataset.current;
							button.dataset.total = nextButton.dataset.total;
							button.classList.remove('is-loading');
							button.removeAttribute('aria-busy');
							button.querySelector('span').textContent = 'Carregar mais produtos';
						} else {
							button.hidden = true;
							if (status) status.textContent += ' Todos os produtos foram exibidos.';
						}
					})
					.catch(function () {
						button.classList.remove('is-loading');
						button.removeAttribute('aria-busy');
						button.querySelector('span').textContent = 'Tentar novamente';
						if (status) status.textContent = 'Não foi possível carregar agora. O link continua disponível.';
					});
			});
		});

		document.querySelectorAll('[data-abs-show-less]').forEach(function (lessButton) {
			if (lessButton.dataset.absReady === 'true') return;
			lessButton.dataset.absReady = 'true';
			lessButton.addEventListener('click', function () {
				var container = lessButton.closest('[data-abs-catalog-products]');
				var loadButton = container ? container.querySelector('[data-abs-load-more]') : null;
				if (!container || !loadButton) return;
				container.querySelectorAll('.abs-loaded-product').forEach(function (card) { card.remove(); });
				loadButton.href = loadButton.dataset.initialHref;
				loadButton.dataset.current = loadButton.dataset.initialCurrent;
				loadButton.hidden = false;
				loadButton.classList.remove('is-loading');
				loadButton.removeAttribute('aria-busy');
				loadButton.querySelector('span').textContent = 'Carregar mais produtos';
				lessButton.hidden = true;
				var status = lessButton.parentNode.querySelector('.attar-load-more__status');
				if (status) status.textContent = 'A lista voltou aos produtos iniciais.';
				container.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		});

		document.addEventListener('keydown', function (event) {
			if (event.key !== 'Escape') return;
			var filters = document.querySelector('[data-abs-filters].is-open');
			if (!filters) return;
			filters.classList.remove('is-open');
			document.body.classList.remove('abs-lock-scroll');
			var toggle = document.querySelector('[data-abs-filter-toggle]');
			if (toggle) { toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); }
		});
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
    else initialize();
})();
