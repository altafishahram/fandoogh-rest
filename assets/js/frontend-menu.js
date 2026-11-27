/**
 * TastyMenu Pro - Frontend Menu Interactions
 */

(function ($) {
    'use strict';

    function escHtml(str) {
        return String(str === null || str === undefined ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    $(document).ready(function () {

        // 0. Day / Night Theme Engine (Persistent via LocalStorage with configurable default)
        function initThemeEngine() {
            var defaultTheme = (window.rmmConfig && window.rmmConfig.default_theme) ? window.rmmConfig.default_theme : 'dark';
            var savedTheme = localStorage.getItem('rmm_theme_mode') || defaultTheme;
            if (savedTheme === 'light') {
                $('body, html, .rmm-menu-widget-wrapper').addClass('light-theme').removeClass('dark-theme');
            } else {
                $('body, html, .rmm-menu-widget-wrapper').removeClass('light-theme').addClass('dark-theme');
            }

            function updateBtn(isLight) {
                $('.rmm-theme-toggle-btn, #theme-toggle-btn').each(function () {
                    var $b = $(this);
                    var iconType = $b.attr('data-icon-type') || 'half_circle';
                    var icon = '🌓';
                    if (iconType === 'moon_sun') {
                        icon = isLight ? '☀️' : '🌙';
                    } else if (iconType === 'sun_stars') {
                        icon = isLight ? '☀️' : '✨';
                    } else if (iconType === 'lantern') {
                        icon = isLight ? '💡' : '🏮';
                    } else {
                        icon = isLight ? '🌗' : '🌓';
                    }
                    $b.find('.rmm-theme-icon').text(icon);
                    var $t = $b.find('.rmm-theme-text');
                    if ($t.length) {
                        $t.text(isLight ? 'حالت شب' : 'حالت روز');
                    }
                });
            }

            if ($('body').hasClass('light-theme')) {
                updateBtn(true);
            }

            $(document).on('click', '#theme-toggle-btn, .rmm-theme-toggle-btn', function (e) {
                e.preventDefault();
                var willBeLight = !$('body').hasClass('light-theme');
                if (willBeLight) {
                    $('body, html, .rmm-menu-widget-wrapper').addClass('light-theme').removeClass('dark-theme');
                    localStorage.setItem('rmm_theme_mode', 'light');
                } else {
                    $('body, html, .rmm-menu-widget-wrapper').removeClass('light-theme').addClass('dark-theme');
                    localStorage.setItem('rmm_theme_mode', 'dark');
                }
                updateBtn(willBeLight);
            });
        }
        initThemeEngine();

        var modal = $('#rmm-modifiers-modal');
        var modalContent = $('#rmm-modal-content');

        function getAddToCartUrl() {
            if (window.rmmConfig && window.rmmConfig.wc_ajax_url) {
                return window.rmmConfig.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart');
            }
            return (window.rmmConfig && window.rmmConfig.ajax_url) ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php';
        }

        function getFragmentsUrl() {
            if (window.rmmConfig && window.rmmConfig.wc_ajax_url) {
                return window.rmmConfig.wc_ajax_url.toString().replace('%%endpoint%%', 'get_refreshed_fragments');
            }
            return (window.rmmConfig && window.rmmConfig.ajax_url) ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php';
        }

        function syncFloatingCartVisibility() {
            var $bar = $('#rmm-floating-cart-bar, .rmm-floating-cart-wrapper');
            if (!$bar.length) return;
            var $cnt = $('#rmm-cart-count');
            if (!$cnt.length) return;
            var rawCount = $cnt.attr('data-count');
            var count = rawCount !== undefined ? parseInt(rawCount, 10) : parseInt($cnt.text().replace(/[^\d]/g, ''), 10);
            if (!isNaN(count) && count > 0) {
                $bar.removeClass('rmm-hidden-cart');
            } else if (!isNaN(count) && count === 0) {
                $bar.addClass('rmm-hidden-cart');
            }
        }

        function applyCartFragments(fragments) {
            if (!fragments || typeof fragments !== 'object') return false;
            var applied = false;
            $.each(fragments, function (key, value) {
                if (typeof key === 'string' && typeof value === 'string') {
                    var $targets = $(key);
                    if ($targets.length) {
                        $targets.replaceWith(value);
                        applied = true;
                    }
                }
            });
            syncFloatingCartVisibility();
            return applied;
        }

        function updateFloatingCart(res) {
            if (res && res.fragments) {
                applyCartFragments(res.fragments);
                return;
            }
            var useWcAjax = !!(window.rmmConfig && window.rmmConfig.wc_ajax_url);
            $.ajax({
                url: getFragmentsUrl(),
                type: 'POST',
                dataType: 'json',
                data: useWcAjax ? {} : { action: 'woocommerce_get_refreshed_fragments' },
                success: function (fragRes) {
                    if (fragRes && fragRes.fragments) {
                        applyCartFragments(fragRes.fragments);
                    }
                }
            });
        }

        $(document.body).on('added_to_cart wc_fragments_refreshed wc_fragments_loaded', function (e, fragments) {
            if (fragments && typeof fragments === 'object') {
                applyCartFragments(fragments);
            } else {
                syncFloatingCartVisibility();
            }
        });

        $(document.body).on('removed_from_cart updated_cart_totals', function (e, fragments) {
            if (fragments && typeof fragments === 'object') {
                applyCartFragments(fragments);
            } else {
                updateFloatingCart();
            }
        });

        // If full-page cache served count=0 while WC cart cookie exists, refresh fragments once
        if (!(window.rmmConfig && window.rmmConfig.is_view_only)) {
            if ($('#rmm-floating-cart-bar').length && document.cookie.indexOf('woocommerce_cart_hash=') !== -1) {
                var initCount = parseInt($('#rmm-cart-count').attr('data-count') || $('#rmm-cart-count').text(), 10) || 0;
                if (initCount === 0) {
                    updateFloatingCart();
                }
            }
        }

        // Lightbox state variables
        var activeModalImages = [];
        var activeLightboxIndex = 0;

        var modalReturnFocus = null;

        function getModalFocusable($modal) {
            return $modal.find('a[href], area[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, object, embed, [contenteditable="true"], [tabindex]:not([tabindex="-1"])').filter(':visible');
        }

        function openAccessibleModal($modal, duration, opener) {
            if (!$modal || !$modal.length) return;
            if (opener && opener.length && opener.get(0) !== document.body && opener.get(0) !== document.documentElement) {
                modalReturnFocus = opener.get(0);
            } else if (!modalReturnFocus) {
                modalReturnFocus = document.activeElement;
            }
            $modal.attr({
                role: 'dialog',
                'aria-modal': 'true',
                'aria-hidden': 'false'
            });
            $('body, html').addClass('rmm-modal-open');
            $modal.stop(true, true).fadeIn(duration || 150, function () {
                var $focusable = getModalFocusable($modal);
                var $target = $modal.find('.rmm-modal-close-btn, .rmm-modal-close, [data-modal-autofocus]').filter(':visible').first();
                if (!$target.length) $target = $focusable.first();
                if ($target.length) $target.trigger('focus');
                else $modal.attr('tabindex', '-1').trigger('focus');
            });
        }

        function closeAccessibleModal($modal, duration) {
            if (!$modal || !$modal.length) return;
            $modal.attr('aria-hidden', 'true').stop(true, true).fadeOut(duration || 200, function () {
                var target = modalReturnFocus;
                modalReturnFocus = null;
                if (target && document.documentElement.contains(target) && $(target).is(':visible') && !$(target).is(':disabled')) {
                    target.focus();
                }
            });
            $('body, html').removeClass('rmm-modal-open');
        }

        function ensureModalContainer() {
            var $m = $('#rmm-modifiers-modal');
            if (!$m.length) {
                $('body').append(
                    '<div id="rmm-modifiers-modal" class="rmm-modal-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-hidden="true" aria-label="جزئیات غذا">' +
                    '    <div class="rmm-modal-box">' +
                    '        <button class="rmm-modal-close-btn" type="button" aria-label="بستن پنجره">&times;</button>' +
                    '        <div id="rmm-modal-content"></div>' +
                    '    </div>' +
                    '</div>'
                );
                $m = $('#rmm-modifiers-modal');
            }
            return {
                modal: $m,
                modalContent: $m.find('#rmm-modal-content')
            };
        }

        // 1. Live Search Filter
        $('.rmm-menu-search-field').on('input', function () {
            var val = $(this).val().toLowerCase().trim();
            if (!val) {
                $('.rmm-food-card').show();
                $('.rmm-category-section').show();
                return;
            }

            if ($('.rmm-category-section').length) {
                $('.rmm-category-section').each(function () {
                    var $sec = $(this);
                    var matchedCount = 0;
                    $sec.find('.rmm-food-card').each(function () {
                        var title = $(this).attr('data-title') || '';
                        var text = $(this).text().toLowerCase();
                        if (title.indexOf(val) !== -1 || text.indexOf(val) !== -1) {
                            $(this).show();
                            matchedCount++;
                        } else {
                            $(this).hide();
                        }
                    });
                    if (matchedCount > 0) {
                        $sec.show();
                    } else {
                        $sec.hide();
                    }
                });
            } else {
                $('.rmm-food-card').each(function () {
                    var title = $(this).attr('data-title') || '';
                    var text = $(this).text().toLowerCase();
                    if (title.indexOf(val) !== -1 || text.indexOf(val) !== -1) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
            }
        });

        // 2. Category Nav Pills: Smooth Scroll + ScrollSpy when category headers exist, otherwise instant filter
        var isProgrammaticScroll = false;
        var programmaticScrollTimer = null;
        var lastSpyCatId = null;

        function getStickyNavOffset() {
            var offset = 16;
            var $stickyBar = $('.rmm-category-nav-wrapper.rmm-sticky-bar').first();
            if ($stickyBar.length) {
                offset += $stickyBar.outerHeight() || 0;
            }
            var $adminBar = $('#wpadminbar');
            if ($adminBar.length && $adminBar.is(':visible')) {
                offset += $adminBar.outerHeight() || 0;
            }
            return offset;
        }

        function scrollPillIntoView($pill) {
            if (!$pill || !$pill.length) return;
            var $scroller = $pill.closest('.rmm-cat-scroll-container');
            if (!$scroller.length) {
                $scroller = $pill.closest('.rmm-category-nav-wrapper');
            }
            if (!$scroller.length || !$scroller[0].scrollBy) return;

            var sRect = $scroller[0].getBoundingClientRect();
            var pRect = $pill[0].getBoundingClientRect();
            var delta = (pRect.left + (pRect.width / 2)) - (sRect.left + (sRect.width / 2));
            if (Math.abs(delta) > 4) {
                $scroller[0].scrollBy({ left: delta, behavior: 'smooth' });
            }
        }

        function setActiveCategoryPill(catId) {
            var idStr = String(catId);
            var $targetPill = $('.rmm-cat-pill').filter(function () {
                return String($(this).data('cat-id') || $(this).data('category')) === idStr;
            });
            if ($targetPill.length) {
                $('.rmm-cat-pill').removeClass('active');
                $targetPill.addClass('active');
                scrollPillIntoView($targetPill.first());
            }
            $('.rmm-catsheet-card').each(function () {
                var $card = $(this);
                var isAct = String($card.data('cat-id')) === idStr;
                $card.toggleClass('is-active', isAct).attr('aria-pressed', isAct ? 'true' : 'false');
            });
        }

        $(document).on('click', '.rmm-cat-pill', function (e) {
            e.preventDefault();
            var $clicked = $(this);
            var catId = $clicked.data('cat-id') || $clicked.data('category');

            $('.rmm-cat-pill').removeClass('active');
            $clicked.addClass('active');
            scrollPillIntoView($clicked);

            var $catalogSections = $('.rmm-menu-catalog-wrap .rmm-category-section');
            if ($catalogSections.length) {
                // Sectioned menu mode: keep all sections visible & smooth scroll to target header
                $catalogSections.show();
                $('.rmm-food-card').show();

                var targetTop = null;
                if (String(catId) === 'all') {
                    var $wrap = $('.rmm-menu-catalog-wrap').first();
                    if ($wrap.length) {
                        targetTop = $wrap.offset().top - getStickyNavOffset();
                    }
                } else {
                    var $targetSec = $('#rmm-cat-sec-' + catId);
                    if (!$targetSec.length) {
                        $targetSec = $catalogSections.filter('[data-cat-id="' + catId + '"]').first();
                    }
                    if ($targetSec.length) {
                        targetTop = $targetSec.offset().top - getStickyNavOffset();
                    }
                }

                if (targetTop !== null) {
                    isProgrammaticScroll = true;
                    lastSpyCatId = String(catId);
                    if (programmaticScrollTimer) clearTimeout(programmaticScrollTimer);
                    window.scrollTo({
                        top: Math.max(0, targetTop),
                        behavior: 'smooth'
                    });
                    programmaticScrollTimer = setTimeout(function () {
                        isProgrammaticScroll = false;
                    }, 750);
                }
                return;
            }

            if (catId === 'all') {
                $('.rmm-category-section').show();
                $('.rmm-food-card').show();
            } else {
                // Filter categories: show only the selected category
                $('.rmm-category-section').each(function () {
                    var secCat = $(this).data('cat-id');
                    if (secCat == catId) {
                        $(this).show();
                        $(this).find('.rmm-food-card').show();
                    } else {
                        $(this).hide();
                    }
                });

                // Also filter any standalone food cards
                $('.rmm-food-card').each(function () {
                    var cats = ($(this).data('cats') + '').split(',');
                    if (cats.indexOf(catId + '') !== -1) {
                        $(this).show();
                    } else {
                        if (!$(this).closest('.rmm-category-section').length) {
                            $(this).hide();
                        }
                    }
                });
            }
        });

        // ScrollSpy: Automatically highlight & scroll active category pill as user scrolls through sections
        if ($('.rmm-menu-catalog-wrap .rmm-category-section').length && $('.rmm-cat-pill').length) {
            var spyRafPending = false;

            function updateCategoryScrollSpy() {
                spyRafPending = false;
                if (isProgrammaticScroll) return;

                var $sections = $('.rmm-menu-catalog-wrap .rmm-category-section:visible');
                if (!$sections.length) return;

                var scrollPos = $(window).scrollTop() + getStickyNavOffset() + 28;
                var activeId = null;

                $sections.each(function () {
                    var $sec = $(this);
                    if (scrollPos >= $sec.offset().top) {
                        activeId = $sec.data('cat-id');
                    }
                });

                if (activeId === null) {
                    var $allPill = $('.rmm-cat-pill[data-cat-id="all"]');
                    activeId = $allPill.length ? 'all' : $sections.first().data('cat-id');
                }

                if (activeId !== null && String(activeId) !== String(lastSpyCatId)) {
                    lastSpyCatId = String(activeId);
                    setActiveCategoryPill(activeId);
                }
            }

            $(window).on('scroll.rmmCategorySpy', function () {
                if (!spyRafPending) {
                    spyRafPending = true;
                    window.requestAnimationFrame(updateCategoryScrollSpy);
                }
            });
        }

        // 3. Quick Add to Cart (No Options)
        $(document).on('click', '.rmm-quick-add-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('id');
            if (!productId) return;

            var originalHtml = $btn.data('rmm-orig-html') || $btn.html();
            $btn.data('rmm-orig-html', originalHtml);
            $btn.prop('disabled', true).text('⏳...');

            var useWcAjax = !!(window.rmmConfig && window.rmmConfig.wc_ajax_url);
            var payload = {
                product_id: productId,
                quantity: 1
            };
            if (!useWcAjax) {
                payload.action = 'woocommerce_add_to_cart';
            }

            $.ajax({
                url: getAddToCartUrl(),
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (res) {
                    if (!res || res.error) {
                        $btn.prop('disabled', false).text(t('error_adding', 'خطا در افزودن!'));
                        setTimeout(function () {
                            $btn.html(originalHtml);
                        }, 1500);
                        return;
                    }

                    $btn.prop('disabled', false).text('✓ ' + t('added_to_cart', 'اضافه شد'));
                    setTimeout(function () {
                        $btn.html(originalHtml);
                    }, 1500);

                    updateFloatingCart(res);
                    $(document.body).trigger('added_to_cart', [res.fragments || {}, res.cart_hash || '', $btn]);
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Client-side instant memory cache for food modals
        var modalDataCache = {};
        var prefetchTimers = {};

        // Intelligent prefetch on hover or touchstart
        $(document).on('mouseenter touchstart', '.rmm-food-card.rmm-card-clickable', function () {
            var $c = $(this);
            var pid = $c.data('id');
            var action = $c.data('action') || 'default_modal';
            if (!pid || action !== 'default_modal') return;

            var langVal = (window.rmmConfig && window.rmmConfig.active_lang) ? window.rmmConfig.active_lang : 'fa';
            var cacheKey = pid + '_' + langVal;
            if (modalDataCache[cacheKey]) return;

            if (prefetchTimers[cacheKey]) clearTimeout(prefetchTimers[cacheKey]);
            prefetchTimers[cacheKey] = setTimeout(function () {
                var nonceVal = (window.rmmConfig && window.rmmConfig.nonce) ? window.rmmConfig.nonce : '';
                $.ajax({
                    url: (window.rmmConfig && window.rmmConfig.ajax_url) ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                    type: 'GET',
                    data: {
                        action: 'rmm_get_product_modifiers',
                        product_id: pid,
                        security: nonceVal,
                        lang: langVal
                    },
                    success: function (res) {
                        if (res && res.success && res.data) {
                            modalDataCache[cacheKey] = res.data;
                        }
                    }
                });
            }, 60);
        });

        // 4. Open Food Details / Modifiers Modal (on Card Click or Button Click)
        // Standalone & Loop Widgets Order button handler
        $(document).on('click', '.rmm-food-card-standalone .rmm-btn-order, .rmm-food-loop-grid .rmm-btn-order', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var pid = $btn.data('product-id') || $btn.data('id');
            if (!pid) return;

            if (isViewOnly()) {
                var $card = $btn.closest('.rmm-food-card');
                var title = $btn.attr('data-title') || $card.find('.rmm-food-title').first().text().trim() || '';
                var rawPrice = parseFloat($btn.attr('data-price'));
                if (isNaN(rawPrice)) {
                    var priceTxt = $card.find('.rmm-food-price ins .amount, .rmm-food-price .amount').first().text().replace(/[^\d]/g, '');
                    rawPrice = parseFloat(priceTxt) || 0;
                }
                var img = $btn.attr('data-image') || $card.find('img.rmm-food-img').first().attr('src') || '';
                addToCatalogList({
                    id: parseInt(pid, 10),
                    title: title,
                    price: isNaN(rawPrice) ? 0 : rawPrice,
                    image: img,
                    qty: 1,
                    meta: ''
                }, $btn);
                return;
            }

            var $btn = $(this);
            var originalHtml = $btn.data('rmm-orig-html') || $btn.html();
            $btn.data('rmm-orig-html', originalHtml);
            $btn.prop('disabled', true);

            var useWcAjax = !!(window.rmmConfig && window.rmmConfig.wc_ajax_url);
            var payload = {
                product_id: pid,
                quantity: 1
            };
            if (!useWcAjax) {
                payload.action = 'woocommerce_add_to_cart';
            }

            $.ajax({
                url: getAddToCartUrl(),
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (res) {
                    $btn.prop('disabled', false);
                    if (!res || res.error) {
                        $btn.text(t('error_adding', 'خطا!'));
                        setTimeout(function () {
                            $btn.html(originalHtml);
                        }, 1500);
                        return;
                    }

                    $btn.text('✓ ' + t('added_to_cart', 'اضافه شد'));
                    setTimeout(function () {
                        $btn.html(originalHtml);
                    }, 1200);

                    updateFloatingCart(res);
                    $(document.body).trigger('added_to_cart', [res.fragments || {}, res.cart_hash || '', $btn]);
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalHtml);
                }
            });
        });

        // Standalone & Loop Widgets Details / Modal handler (.rmm-open-modal)
        $(document).on('click', '.rmm-open-modal', function (e) {
            if ($(e.target).closest('.rmm-quick-add-btn, .rmm-btn-order').length) {
                return;
            }
            e.preventDefault();
            var pid = $(this).data('product-id') || $(this).closest('.rmm-food-card').data('product-id');
            if (!pid) return;

            var $card = $(this).closest('.rmm-food-card');
            var $opener = $(this).closest('button, a, [tabindex]').first();
            if (!$opener.length && $card.length) {
                $opener = $card.find('button.rmm-btn-details, button.rmm-btn-order, a').filter(':visible').first();
            }
            if ($opener.length) modalReturnFocus = $opener.get(0);
            loadDefaultModal(pid, $card);
        });

        // 4. Open Food Details / Modifiers Modal (on Card Click or Button Click)
        $(document).on('click', '.rmm-food-card.rmm-card-clickable, .rmm-open-modifiers-btn', function (e) {
            // Ignore click if user clicked directly on catalog WhatsApp/call button or quick-add
            if ($(e.target).closest('.rmm-quick-add-btn, .rmm-catalog-add-btn, .rmm-btn-order.whatsapp, .rmm-btn-order.call').length) {
                return;
            }
            e.preventDefault();

            var $card = $(this).closest('.rmm-food-card');
            var $opener = $(this).closest('button, a, [tabindex]').first();
            if (!$opener.length && $card.length) {
                $opener = $card.find('button.rmm-btn-details, button.rmm-btn-order, a').filter(':visible').first();
            }
            if ($opener.length) modalReturnFocus = $opener.get(0);
            var productId = $card.data('id') || $(this).data('id');
            var action = $card.data('action') || 'default_modal';
            var templateId = $card.data('template-id');
            var popupId = $card.data('popup-id');

            if (action === 'none') {
                return;
            }

            // A) Elementor Pro Popup action
            if (action === 'elementor_popup' && popupId) {
                if (window.elementorProFrontend && elementorProFrontend.modules && elementorProFrontend.modules.popup) {
                    elementorProFrontend.modules.popup.showPopup({ id: popupId });
                }
                $(document).trigger('rmm_product_selected', [productId, $card]);
                return;
            }

            // B) Custom Elementor Saved Template
            if (action === 'elementor_template' && templateId && templateId != '0') {
                var tplContainer = ensureModalContainer();
                tplContainer.modalContent.html('<div class="rmm-modal-loading-box"><span class="rmm-modal-spinner">⏳</span><p>در حال بارگذاری قالب المنتور...</p></div>');
                openAccessibleModal(tplContainer.modal, 200, $(e.target).closest('button, a, [tabindex]').first());


                var nonceVal = (window.rmmConfig && window.rmmConfig.nonce) ? window.rmmConfig.nonce : '';
                var langVal = (window.rmmConfig && window.rmmConfig.active_lang) ? window.rmmConfig.active_lang : 'fa';

                $.ajax({
                    url: window.rmmConfig ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                    type: 'GET',
                    data: {
                        action: 'rmm_render_food_elementor_template',
                        template_id: templateId,
                        product_id: productId,
                        security: nonceVal,
                        lang: langVal
                    },
                    success: function (res) {
                        if (res && res.success && res.data && res.data.html) {
                            tplContainer.modalContent.html('<div class="rmm-custom-template-wrap">' + res.data.html + '</div>');
                        } else {
                            loadDefaultModal(productId, $card);
                        }
                    },
                    error: function () {
                        loadDefaultModal(productId, $card);
                    }
                });
                return;
            }

            // C) Default Rich Modal
            loadDefaultModal(productId, $card);
        });

        // Helper for localized strings
        function t(key, fallback) {
            if (window.rmmConfig && window.rmmConfig.strings && window.rmmConfig.strings[key]) {
                return window.rmmConfig.strings[key];
            }
            return fallback;
        }

        // Format price number according to active locale
        function formatPriceNumber(num) {
            var lang = (window.rmmConfig && window.rmmConfig.active_lang) ? window.rmmConfig.active_lang : 'fa';
            var locale = (lang === 'fa') ? 'fa-IR' : (lang === 'ar' ? 'ar-SA' : 'en-US');
            var clean = Math.round(Number(num) || 0);
            return clean.toLocaleString(locale);
        }

        function loadDefaultModal(productId, $card) {
            var container = ensureModalContainer();
            var $curModal = container.modal;
            var $curContent = container.modalContent;
            var nonceVal = (window.rmmConfig && window.rmmConfig.nonce) ? window.rmmConfig.nonce : '';
            var langVal = (window.rmmConfig && window.rmmConfig.active_lang) ? window.rmmConfig.active_lang : 'fa';
            var cacheKey = productId + '_' + langVal;

            // 1. Instant hit in client-side memory cache (0ms latency!)
            if (modalDataCache[cacheKey]) {
                renderFoodDetailsModal(modalDataCache[cacheKey]);
                openAccessibleModal($curModal, 150, $(document.activeElement));

                return;
            }

            // 2. Instant optimistic preview extracted from clicked card (Zero-delay feel)
            if ($card && $card.length) {
                var cardImg = $card.find('.rmm-card-img').attr('src') || '';
                var cardTitle = $card.find('.rmm-food-title').text().trim() || '';
                var cardPriceHtml = $card.find('.rmm-food-price').html() || '';
                var cardDesc = $card.find('.rmm-food-desc').text().trim() || '';
                var cardIngredients = $card.find('.rmm-food-ingredients').text().trim() || '';

                var optHtml = '<div class="rmm-modal-hero-wrap">' +
                    (cardImg ? '<img src="' + escHtml(cardImg) + '" class="rmm-modal-hero-img" alt="' + escHtml(cardTitle) + '">' : '') +
                    '</div>' +
                    '<div class="rmm-modal-body">' +
                    '<div class="rmm-modal-title-row"><h3 class="rmm-modal-food-title">' + escHtml(cardTitle) + '</h3></div>' +
                    '<div class="rmm-modal-price-row"><div class="rmm-modal-food-price">' + cardPriceHtml + '</div></div>' +
                    (cardDesc ? '<div class="rmm-modal-desc-box"><p class="rmm-modal-desc-text">' + escHtml(cardDesc) + '</p></div>' : '') +
                    (cardIngredients ? '<div class="rmm-modal-ingredients-box"><p class="rmm-modal-ingredients-text">' + escHtml(cardIngredients) + '</p></div>' : '') +
                    '<div class="rmm-modal-loading-box" style="padding:15px 0;"><span class="rmm-modal-spinner">🍲</span><p style="margin:0; font-size:12px; font-weight:600; color:#64748b;">' + escHtml(t('loading', 'در حال دریافت گزینه‌ها...')) + '</p></div>' +
                    '</div>';

                $curContent.html(optHtml);
            } else {
                $curContent.html('<div class="rmm-modal-loading-box"><span class="rmm-modal-spinner">🍲</span><p style="margin:0; font-weight:600; color:#475569;">' + escHtml(t('loading', 'در حال بارگذاری جزئیات غذا...')) + '</p></div>');
            }

            openAccessibleModal($curModal, 150, $(document.activeElement));


            // 3. Perform background fetch
            $.ajax({
                url: (window.rmmConfig && window.rmmConfig.ajax_url) ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'GET',
                data: {
                    action: 'rmm_get_product_modifiers',
                    product_id: productId,
                    security: nonceVal,
                    lang: langVal
                },
                success: function (res) {
                    try {
                        if (res && res.success && res.data) {
                            modalDataCache[cacheKey] = res.data;
                            renderFoodDetailsModal(res.data);
                        } else {
                            var errMsg = (res && res.data && res.data.message) ? res.data.message : t('error_loading', 'خطا در دریافت اطلاعات غذا.');
                            $curContent.html('<p style="color:#ef4444; text-align:center; padding:40px 20px;">' + escHtml(errMsg) + '</p>');
                        }
                    } catch (renderErr) {
                        console.error('Error rendering food modal:', renderErr);
                        $curContent.html('<p style="color:#ef4444; text-align:center; padding:40px 20px;">' + escHtml(t('error_loading', 'خطا در نمایش اطلاعات غذا.')) + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error loading food modal:', status, error);
                    $curContent.html('<p style="color:#ef4444; text-align:center; padding:40px 20px;">' + escHtml(t('error_server', 'خطای ارتباط با سرور.')) + '</p>');
                }
            });
        }

        function renderFoodDetailsModal(data) {
            var html = '';

            // Store current images for fullscreen lightbox
            activeModalImages = (data.images && data.images.length) ? data.images : (data.image ? [data.image] : []);
            activeLightboxIndex = 0;

            // 1. Hero Image / Interactive Slider (Album)
            if (data.images && data.images.length > 1) {
                var slidesHtml = '';
                var dotsHtml = '';
                data.images.forEach(function (img, idx) {
                    var activeClass = (idx === 0) ? ' active' : '';
                    slidesHtml += '<div class="rmm-modal-slide' + activeClass + '" data-index="' + idx + '">' +
                        '<img src="' + escHtml(img) + '" class="rmm-modal-hero-img" alt="' + escHtml(data.name) + '">' +
                        '</div>';
                    dotsHtml += '<span class="rmm-slider-dot' + activeClass + '" data-index="' + idx + '"></span>';
                });

                html += '<div class="rmm-modal-hero-wrap rmm-modal-slider-wrap" data-current-slide="0" data-total-slides="' + data.images.length + '">' +
                    '<div class="rmm-modal-slider-viewport">' +
                    '<div class="rmm-modal-slider-track">' + slidesHtml + '</div>' +
                    '</div>' +
                    '<button type="button" class="rmm-slider-arrow rmm-slider-prev" aria-label="قبلی">&#10094;</button>' +
                    '<button type="button" class="rmm-slider-arrow rmm-slider-next" aria-label="بعدی">&#10095;</button>' +
                    '<div class="rmm-slider-dots">' + dotsHtml + '</div>' +
                    '<span class="rmm-slider-counter"><span class="rmm-slide-current">1</span> / <span class="rmm-slide-total">' + data.images.length + '</span></span>' +
                    '<div class="rmm-modal-zoom-hint">🔍 ' + escHtml(t('view_image', 'مشاهده بزرگ‌تر')) + '</div>' +
                    (data.badge ? '<span class="rmm-modal-badge-overlay ' + escHtml(data.badge.class || '') + '">' + (data.badge.image ? '<img src="' + escHtml(data.badge.image) + '" class="rmm-badge-icon-img" alt=""> ' : (data.badge.icon ? escHtml(data.badge.icon) + ' ' : '')) + escHtml(data.badge.label || '') + '</span>' : '') +
                    '</div>';
            } else if (data.image) {
                html += '<div class="rmm-modal-hero-wrap">' +
                    '<img src="' + escHtml(data.image) + '" class="rmm-modal-hero-img" alt="' + escHtml(data.name) + '">' +
                    '<div class="rmm-modal-zoom-hint">🔍 ' + escHtml(t('view_image', 'مشاهده بزرگ‌تر')) + '</div>' +
                    (data.badge ? '<span class="rmm-modal-badge-overlay ' + escHtml(data.badge.class || '') + '">' + (data.badge.image ? '<img src="' + escHtml(data.badge.image) + '" class="rmm-badge-icon-img" alt=""> ' : (data.badge.icon ? escHtml(data.badge.icon) + ' ' : '')) + escHtml(data.badge.label || '') + '</span>' : '') +
                    '</div>';
            } else {
                html += '<div style="position:relative; padding-top:20px; padding-left:20px;"></div>';
            }

            // 2. Modal Body
            html += '<div class="rmm-modal-body">';

            // Title & Price Row (Proper strikethrough sale price vs regular)
            var priceMarkup = '';
            if (data.catalog_hide_price && data.is_view_only) {
                priceMarkup = '<span class="rmm-price-hidden">' + escHtml(t('digital_menu', 'منو دیجیتال')) + '</span>';
            } else if (data.is_on_sale && data.formatted_regular) {
                priceMarkup = '<div class="rmm-modal-price-wrap"><del class="rmm-modal-old-price">' + data.formatted_regular + '</del> <ins class="rmm-modal-sale-price">' + data.formatted_price + '</ins></div>';
            } else {
                priceMarkup = '<span class="rmm-modal-regular-price">' + data.formatted_price + '</span>';
            }

            // 1. Food Name Row (Full Width Line)
            html += '<div class="rmm-modal-title-row">' +
                '<h3 class="rmm-modal-food-title">' + escHtml(data.name) + '</h3>' +
                '</div>';

            // 2. Food Price Row (Full Width Line Below Name)
            html += '<div class="rmm-modal-price-row">' +
                '<div class="rmm-modal-food-price" id="rmm-calc-price">' + priceMarkup + '</div>' +
                '</div>';

            // 3. Chips Row (Badges, Features, Prep Time, Calories)
            var chips = [];
            if (data.badge && (data.badge.label || data.badge.image)) {
                var bImg = data.badge.image ? '<img src="' + escHtml(data.badge.image) + '" class="rmm-badge-icon-img" alt=""> ' : '';
                var bIcon = (!data.badge.image && data.badge.icon) ? escHtml(data.badge.icon) + ' ' : '';
                chips.push('<span class="rmm-modal-chip ' + escHtml(data.badge.class || '') + '">' + bImg + bIcon + escHtml(data.badge.label || '') + '</span>');
            }
            if (data.prep_time) {
                chips.push('<span class="rmm-modal-chip">⏱️ ' + escHtml(data.prep_time) + ' ' + escHtml(t('minutes', 'دقیقه')) + '</span>');
            }
            if (data.calories) {
                chips.push('<span class="rmm-modal-chip calories">🔥 ' + escHtml(t('calories_label', 'ارزش غذایی و کالری:')) + ' ' + escHtml(data.calories) + ' ' + escHtml(t('kcal', 'کالری')) + '</span>');
            }
            if (chips.length) {
                html += '<div class="rmm-modal-chips-row">' + chips.join('') + '</div>';
            }

            // 4. Description Box (توضیحات محصول)
            if (data.description) {
                html += '<div class="rmm-modal-desc-box">' +
                    '<p class="rmm-modal-desc-text">' + escHtml(data.description) + '</p>' +
                    '</div>';
            }

            // 5. Ingredients Box (موارد تشکیل‌دهنده و ترکیبات)
            if (data.ingredients) {
                html += '<div class="rmm-modal-ingredients-box">' +
                    '<h4>🥗 ' + escHtml(t('ingredients', 'موارد تشکیل‌دهنده و ترکیبات:')) + '</h4>' +
                    '<p class="rmm-modal-ingredients-text">' + escHtml(data.ingredients) + '</p>' +
                    '</div>';
            }

            // Form container
            html += '<form id="rmm-modal-add-form" data-product-id="' + escHtml(data.id) + '" data-base-price="' + escHtml(data.base_price) + '" data-currency="' + escHtml(data.currency_symbol) + '">';

            // Portions / Sizes
            if (data.portions && data.portions.length > 0) {
                html += '<div class="rmm-modal-section-box">' +
                    '<span class="rmm-modal-section-title">📏 ' + escHtml(t('portions', 'انتخاب سایز یا پرس:')) + '</span>';
                data.portions.forEach(function (port, idx) {
                    var isChecked = (idx === 0) ? 'checked' : '';
                    var isSelected = (idx === 0) ? 'is-selected' : '';
                    html += '<label class="rmm-modal-portion-item ' + isSelected + '">' +
                        '<span><input type="radio" name="rmm_portion" value="' + idx + '" data-price="' + escHtml(port.price) + '" ' + isChecked + '> ' + escHtml(port.name) + '</span>' +
                        '<span class="rmm-modal-addon-price">' + escHtml(formatPriceNumber(port.price) + ' ' + data.currency_symbol) + '</span>' +
                        '</label>';
                });
                html += '</div>';
            }

            // Modifier Groups / Addons
            if (data.modifier_groups && data.modifier_groups.length > 0) {
                data.modifier_groups.forEach(function (group, gIdx) {
                    html += '<div class="rmm-modal-section-box">' +
                        '<span class="rmm-modal-section-title">🥗 ' + escHtml(group.title) + ':</span>';

                    if (group.options && group.options.length > 0) {
                        group.options.forEach(function (opt) {
                            var inputType = (group.type === 'radio') ? 'radio' : 'checkbox';
                            var inputName = (group.type === 'radio') ? 'rmm_radio_addon_' + gIdx : 'rmm_addons[]';
                            var optJson = JSON.stringify({ name: opt.name, price: opt.price, group: group.title });
                            var priceTag = (opt.price > 0) ? '(+' + formatPriceNumber(opt.price) + ' ' + data.currency_symbol + ')' : '(' + t('free', 'رایگان') + ')';

                            html += '<label class="rmm-modal-addon-item">' +
                                '<span><input type="' + inputType + '" name="' + inputName + '" value="' + escHtml(optJson) + '" data-price="' + escHtml(opt.price) + '"> ' + escHtml(opt.name) + '</span>' +
                                '<span class="rmm-modal-addon-price">' + escHtml(priceTag) + '</span>' +
                                '</label>';
                        });
                    }
                    html += '</div>';
                });
            }

            // Ordering controls (if not view_only)
            if (!data.is_view_only) {
                // Quantity Counter
                html += '<div class="rmm-modal-qty-container">' +
                    '<span class="rmm-modal-qty-label">' + escHtml(t('quantity', 'تعداد سفارش:')) + '</span>' +
                    '<div class="rmm-qty-controls">' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-plus">+</button>' +
                    '<input type="number" class="rmm-qty-val" name="quantity" value="1" min="1" max="99" readonly>' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-minus">−</button>' +
                    '</div>' +
                    '</div>';

                // Kitchen Note
                html += '<div class="rmm-modal-kitchen-note" style="margin-bottom:14px;">' +
                    '<input type="text" name="rmm_order_notes" placeholder="' + escHtml(t('kitchen_note_placeholder', 'یادداشت برای آشپزخانه (مثلاً: بدون پیاز، سس جداگانه...)')) + '">' +
                    '</div>';

                // Submit Button
                html += '<div class="rmm-modal-submit-action">' +
                    '<button type="submit" class="rmm-modal-btn-submit" id="rmm-modal-submit-btn">' +
                    '<span>+ ' + escHtml(t('add_to_cart', 'افزودن به سبد خرید')) + '</span>' +
                    '<span id="rmm-modal-btn-total-price">' + escHtml(formatPriceNumber(data.base_price) + ' ' + data.currency_symbol) + '</span>' +
                    '</button>' +
                    '</div>';
            } else {
                // View-Only Catalog Mode: allow adding to floating selection list with qty & options
                html += '<div class="rmm-modal-qty-container">' +
                    '<span class="rmm-modal-qty-label">' + escHtml(t('quantity', 'تعداد انتخاب:')) + '</span>' +
                    '<div class="rmm-qty-controls">' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-plus">+</button>' +
                    '<input type="number" class="rmm-qty-val" name="quantity" value="1" min="1" max="99" readonly>' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-minus">−</button>' +
                    '</div>' +
                    '</div>';

                var hideCatalogPrice = !!(window.rmmConfig && window.rmmConfig.catalog_hide_price) || !!data.catalog_hide_price;
                var modalBtnPriceHtml = hideCatalogPrice ? '' : '<span id="rmm-modal-btn-total-price">' + escHtml(formatPriceNumber(data.base_price) + ' ' + data.currency_symbol) + '</span>';

                html += '<div class="rmm-modal-submit-action" style="display:flex; flex-direction:column; gap:10px;">' +
                    '<button type="button" class="rmm-modal-btn-submit rmm-modal-catalog-add-btn" id="rmm-modal-catalog-add-btn" ' +
                    'data-id="' + escHtml(data.id) + '" ' +
                    'data-title="' + escHtml(data.name) + '" ' +
                    'data-image="' + escHtml(data.image || '') + '" ' +
                    'data-hide-price="' + (hideCatalogPrice ? '1' : '0') + '">' +
                    '<span>+ ' + escHtml(t('add_to_list', 'افزودن به لیست')) + '</span>' +
                    modalBtnPriceHtml +
                    '</button>';

                if (data.action_btn === 'whatsapp' && data.whatsapp_url) {
                    html += '<a href="' + escHtml(data.whatsapp_url) + '" target="_blank" class="rmm-modal-btn-submit" style="background:#25d366; text-decoration:none; justify-content:center; gap:8px;">' +
                        '💬 ' + escHtml(t('order_whatsapp', 'سفارش و استعلام در واتساپ')) +
                        '</a>';
                } else if (data.action_btn === 'call' && data.phone_number) {
                    html += '<a href="tel:' + escHtml(data.phone_number) + '" class="rmm-modal-btn-submit" style="background:#0284c7; text-decoration:none; justify-content:center; gap:8px;">' +
                        '📞 ' + escHtml(t('call_order', 'تماس تلفنی با رستوران')) +
                        '</a>';
                }
                html += '</div>';
            }

            html += '</form></div>'; // Close form and modal-body

            ensureModalContainer().modalContent.html(html);
            calculateModalPrice();
        }

        // --- Interactive Modal Image Slider Handlers --- //
        function setModalSlide(index) {
            var $slider = $('.rmm-modal-slider-wrap');
            if (!$slider.length) return;
            var total = parseInt($slider.data('total-slides'), 10) || 1;
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;

            $slider.data('current-slide', index);
            $slider.find('.rmm-modal-slide').removeClass('active').eq(index).addClass('active');
            $slider.find('.rmm-slider-dot').removeClass('active').eq(index).addClass('active');
            $slider.find('.rmm-slide-current').text(index + 1);

            var isRTL = $('html').attr('dir') === 'rtl' || $('body').hasClass('rtl');
            var offset = index * 100;
            var transformVal = isRTL ? 'translateX(' + offset + '%)' : 'translateX(-' + offset + '%)';
            $slider.find('.rmm-modal-slider-track').css('transform', transformVal);
        }

        $(document).on('click', '.rmm-slider-prev', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var current = parseInt($('.rmm-modal-slider-wrap').data('current-slide'), 10) || 0;
            setModalSlide(current - 1);
        });

        $(document).on('click', '.rmm-slider-next', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var current = parseInt($('.rmm-modal-slider-wrap').data('current-slide'), 10) || 0;
            setModalSlide(current + 1);
        });

        $(document).on('click', '.rmm-slider-dot', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var idx = parseInt($(this).data('index'), 10) || 0;
            setModalSlide(idx);
        });

        // Touch swipe support for mobile
        var touchStartX = 0;
        var touchEndX = 0;
        $(document).on('touchstart', '.rmm-modal-slider-wrap', function(e) {
            if (e.originalEvent && e.originalEvent.touches && e.originalEvent.touches.length) {
                touchStartX = e.originalEvent.touches[0].screenX;
            }
        });
        $(document).on('touchend', '.rmm-modal-slider-wrap', function(e) {
            if (e.originalEvent && e.originalEvent.changedTouches && e.originalEvent.changedTouches.length) {
                touchEndX = e.originalEvent.changedTouches[0].screenX;
                var diff = touchEndX - touchStartX;
                if (Math.abs(diff) > 40) {
                    var current = parseInt($('.rmm-modal-slider-wrap').data('current-slide'), 10) || 0;
                    var isRTL = $('html').attr('dir') === 'rtl' || $('body').hasClass('rtl');
                    if (diff > 0) {
                        setModalSlide(isRTL ? current + 1 : current - 1);
                    } else {
                        setModalSlide(isRTL ? current - 1 : current + 1);
                    }
                }
            }
        });

        // Live calculation on Portions or Addons selection
        $(document).on('change', '#rmm-modal-add-form input', function () {
            calculateModalPrice();
        });

        // Toggle selected classes on portion radio change
        $(document).on('change', '.rmm-modal-portion-item input[type="radio"]', function() {
            $(this).closest('.rmm-modal-section-box').find('.rmm-modal-portion-item').removeClass('is-selected');
            $(this).closest('.rmm-modal-portion-item').addClass('is-selected');
        });

        // Toggle selected classes on addon checkbox/radio change
        $(document).on('change', '.rmm-modal-addon-item input', function() {
            if ($(this).attr('type') === 'radio') {
                $(this).closest('.rmm-modal-section-box').find('.rmm-modal-addon-item').removeClass('is-selected');
                if ($(this).is(':checked')) {
                    $(this).closest('.rmm-modal-addon-item').addClass('is-selected');
                }
            } else {
                $(this).closest('.rmm-modal-addon-item').toggleClass('is-selected', $(this).is(':checked'));
            }
        });

        // Quantity Plus / Minus controls
        $(document).on('click', '.rmm-qty-plus', function (e) {
            e.preventDefault();
            var $input = $(this).siblings('.rmm-qty-val');
            var val = parseInt($input.val(), 10) || 1;
            if (val < 99) {
                $input.val(val + 1);
                calculateModalPrice();
            }
        });

        $(document).on('click', '.rmm-qty-minus', function (e) {
            e.preventDefault();
            var $input = $(this).siblings('.rmm-qty-val');
            var val = parseInt($input.val(), 10) || 1;
            if (val > 1) {
                $input.val(val - 1);
                calculateModalPrice();
            }
        });

        function calculateModalPrice() {
            var form = $('#rmm-modal-add-form');
            if (!form.length) return;

            var basePrice = parseFloat(form.data('base-price')) || 0;
            var currency = form.data('currency') || '';

            // Check selected portion
            var selectedPortion = form.find('input[name="rmm_portion"]:checked');
            if (selectedPortion.length) {
                basePrice = parseFloat(selectedPortion.data('price')) || 0;
            }

            // Add selected addons
            var extra = 0;
            form.find('.rmm-modal-addon-item input:checked').each(function () {
                extra += parseFloat($(this).data('price')) || 0;
            });

            var qty = parseInt(form.find('.rmm-qty-val').val(), 10) || 1;
            var singleTotal = basePrice + extra;
            var grandTotal = singleTotal * qty;

            var $ins = $('#rmm-calc-price .rmm-modal-sale-price, #rmm-calc-price ins');
            if ($ins.length) {
                $ins.text(formatPriceNumber(singleTotal) + ' ' + currency);
            } else {
                var $regular = $('#rmm-calc-price .rmm-modal-regular-price, #rmm-calc-price span');
                if ($regular.length) {
                    $regular.first().text(formatPriceNumber(singleTotal) + ' ' + currency);
                } else {
                    $('#rmm-calc-price').html('<span class="rmm-modal-regular-price">' + formatPriceNumber(singleTotal) + ' ' + currency + '</span>');
                }
            }
            $('#rmm-modal-btn-total-price').text(formatPriceNumber(grandTotal) + ' ' + currency);
        }

        // Submit customized modal form
        $(document).on('submit', '#rmm-modal-add-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $('#rmm-modal-submit-btn');
            var pId = $form.data('product-id');
            var originalBtnHtml = $btn.data('rmm-orig-html') || $btn.html();
            $btn.data('rmm-orig-html', originalBtnHtml);

            $btn.prop('disabled', true).text(t('submitting', 'در حال ثبت سفارش...'));

            var useWcAjax = !!(window.rmmConfig && window.rmmConfig.wc_ajax_url);
            var formData = $form.serializeArray();
            var postData = {
                product_id: pId,
                quantity: 1
            };
            if (!useWcAjax) {
                postData.action = 'woocommerce_add_to_cart';
            }

            formData.forEach(function (item) {
                if (item.name === 'rmm_addons[]' || item.name.indexOf('rmm_radio_addon_') === 0) {
                    if (!postData['rmm_addons']) postData['rmm_addons'] = [];
                    postData['rmm_addons'].push(item.value);
                } else {
                    postData[item.name] = item.value;
                }
            });

            $.ajax({
                url: getAddToCartUrl(),
                type: 'POST',
                dataType: 'json',
                data: postData,
                success: function (res) {
                    if (!res || res.error) {
                        $btn.prop('disabled', false).text(t('error_adding', 'خطا در افزودن!'));
                        setTimeout(function () {
                            $btn.html(originalBtnHtml);
                        }, 1500);
                        return;
                    }

                    $btn.text('✓ ' + t('added_to_cart', 'به سبد اضافه شد'));
                    setTimeout(function () {
                        closeAccessibleModal($('#rmm-modifiers-modal'), 200);
                        $btn.prop('disabled', false).html(originalBtnHtml);
                    }, 700);

                    updateFloatingCart(res);
                    $(document.body).trigger('added_to_cart', [res.fragments || {}, res.cart_hash || '', $btn]);
                },
                error: function () {
                    $btn.prop('disabled', false).text(t('error_adding', 'خطا در افزودن!'));
                    setTimeout(function () {
                        $btn.html(originalBtnHtml);
                    }, 1500);
                }
            });
        });

        // Delegated Close Modal Handler (works for dynamic buttons, backdrop & icons)
        $(document).on('click', '.rmm-modal-close-btn, #rmm-modal-close-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('.rmm-modal-backdrop:visible').each(function () {
                closeAccessibleModal($(this), 200);
            });
        });

        $(document).on('click', '#rmm-modifiers-modal, .rmm-modal-backdrop', function (e) {
            if (e.target === this) {
                closeAccessibleModal($(this), 200);
            }
        });

        // Keyboard support: keep focus inside the active dialog and close it with Escape.
        $(document).on('keydown', function (e) {
            var $activeDialog = $('.rmm-modal-backdrop:visible[aria-modal="true"]').last();
            if (e.key === 'Tab' && $activeDialog.length) {
                var $focusable = getModalFocusable($activeDialog);
                if (!$focusable.length) {
                    e.preventDefault();
                    $activeDialog.attr('tabindex', '-1').trigger('focus');
                    return;
                }
                var first = $focusable.get(0);
                var last = $focusable.get($focusable.length - 1);
                if (e.shiftKey && (document.activeElement === first || !$activeDialog.get(0).contains(document.activeElement))) {
                    e.preventDefault();
                    last.focus();
                } else if (!e.shiftKey && (document.activeElement === last || !$activeDialog.get(0).contains(document.activeElement))) {
                    e.preventDefault();
                    first.focus();
                }
                return;
            }
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('.rmm-modal-backdrop:visible').each(function () {
                    closeAccessibleModal($(this), 200);
                });
                $('#rmm-lightbox-modal').fadeOut(200);
                if (entranceModal && entranceModal.length) entranceModal.fadeOut(200);
            }
        });

        // Fullscreen Lightbox / Gallery Viewer
        function getOrCreateLightbox() {
            var $lb = $('#rmm-lightbox-modal');
            if (!$lb.length) {
                $('body').append(
                    '<div id="rmm-lightbox-modal" class="rmm-lightbox-backdrop" style="display:none;" role="dialog" aria-modal="true">' +
                    '<div class="rmm-lightbox-container">' +
                    '<button type="button" class="rmm-lightbox-close" aria-label="' + t('close', 'بستن') + '">&times;</button>' +
                    '<div class="rmm-lightbox-content">' +
                    '<img src="" id="rmm-lightbox-img" class="rmm-lightbox-img" alt="غذا">' +
                    '</div>' +
                    '<button type="button" class="rmm-lightbox-arrow rmm-lightbox-prev" aria-label="قبلی">&#10094;</button>' +
                    '<button type="button" class="rmm-lightbox-arrow rmm-lightbox-next" aria-label="بعدی">&#10095;</button>' +
                    '<div class="rmm-lightbox-counter"><span id="rmm-lb-cur">1</span> / <span id="rmm-lb-tot">1</span></div>' +
                    '</div>' +
                    '</div>'
                );
                $lb = $('#rmm-lightbox-modal');
            }
            return $lb;
        }

        function openLightbox(index) {
            if (!activeModalImages || !activeModalImages.length) return;
            activeLightboxIndex = Math.max(0, Math.min(index || 0, activeModalImages.length - 1));
            var $lb = getOrCreateLightbox();
            $('#rmm-lightbox-img').attr('src', activeModalImages[activeLightboxIndex]);
            $('#rmm-lb-cur').text(activeLightboxIndex + 1);
            $('#rmm-lb-tot').text(activeModalImages.length);

            if (activeModalImages.length > 1) {
                $lb.find('.rmm-lightbox-arrow, .rmm-lightbox-counter').show();
            } else {
                $lb.find('.rmm-lightbox-arrow, .rmm-lightbox-counter').hide();
            }

            $lb.fadeIn(200);
        }

        $(document).on('click', '.rmm-modal-hero-wrap, .rmm-modal-hero-img, .rmm-modal-slide img, .rmm-modal-zoom-hint', function (e) {
            if ($(e.target).closest('.rmm-modal-close-btn, .rmm-slider-arrow, .rmm-slider-dots').length) return;
            var currentIdx = 0;
            var $wrap = $(this).closest('.rmm-modal-hero-wrap');
            if ($wrap.length && $wrap.attr('data-current-slide') !== undefined) {
                currentIdx = parseInt($wrap.attr('data-current-slide'), 10) || 0;
            }
            openLightbox(currentIdx);
        });

        $(document).on('click', '.rmm-lightbox-close, #rmm-lightbox-modal', function (e) {
            if ($(e.target).closest('.rmm-lightbox-content img, .rmm-lightbox-arrow').length) return;
            $('#rmm-lightbox-modal').fadeOut(200);
        });

        $(document).on('click', '.rmm-lightbox-prev', function (e) {
            e.stopPropagation();
            if (activeModalImages.length < 2) return;
            activeLightboxIndex = (activeLightboxIndex - 1 + activeModalImages.length) % activeModalImages.length;
            $('#rmm-lightbox-img').attr('src', activeModalImages[activeLightboxIndex]);
            $('#rmm-lb-cur').text(activeLightboxIndex + 1);
        });

        $(document).on('click', '.rmm-lightbox-next', function (e) {
            e.stopPropagation();
            if (activeModalImages.length < 2) return;
            activeLightboxIndex = (activeLightboxIndex + 1) % activeModalImages.length;
            $('#rmm-lightbox-img').attr('src', activeModalImages[activeLightboxIndex]);
            $('#rmm-lb-cur').text(activeLightboxIndex + 1);
        });

        // 6. Customer Live View Switcher (Grid vs List)
        $(document).on('click', '.rmm-view-toggle-btn, .rmm-view-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var view = $btn.attr('data-view');
            if (view !== 'grid' && view !== 'list') return;

            var $wrapper = $btn.closest('.rmm-menu-widget-wrapper');
            var $container = $wrapper.find('.rmm-menu-container').first();
            if (!$container.length) return;

            $wrapper.find('.rmm-view-toggle-btn, .rmm-view-btn')
                .removeClass('is-active')
                .attr('aria-pressed', 'false');
            $btn.addClass('is-active').attr('aria-pressed', 'true');

            if (view === 'list') {
                $container.removeClass('rmm-view-grid').addClass('rmm-view-list');
            } else {
                $container.removeClass('rmm-view-list').addClass('rmm-view-grid');
            }

            try {
                localStorage.setItem('rmm_user_view_pref', view);
            } catch (err) {}
        });

        // Restore only known customer preferences; never build a selector from stored data.
        try {
            var savedView = localStorage.getItem('rmm_user_view_pref');
            if (savedView === 'grid' || savedView === 'list') {
                $('.rmm-menu-widget-wrapper').each(function () {
                    $(this).find('.rmm-view-btn[data-view="' + savedView + '"]').first().trigger('click');
                });
            }
        } catch (err) {}



        // 7. Entrance Language Selection Modal
        var entranceModal = $('#rmm-entrance-modal');
        if (entranceModal.length) {
            var cookieChosen = document.cookie.match(/(?:^|;\s*)rmm_lang_chosen=([^;]*)/);
            var localChosen = localStorage.getItem('rmm_lang_chosen');
            var sessionChosen = sessionStorage.getItem('rmm_lang_session_chosen');

            // Show popup if remember was not chosen on previous visits and not yet chosen in this tab
            if (!cookieChosen && !localChosen && !sessionChosen) {
                setTimeout(function () {
                    entranceModal.css('display', 'flex').hide().fadeIn(250);
                }, 300);
            }

            $('#rmm-entrance-close-btn, #rmm-entrance-modal').on('click', function (e) {
                if (e.target === this) {
                    entranceModal.fadeOut(200);
                    sessionStorage.setItem('rmm_lang_session_chosen', '1');
                }
            });

            $('.rmm-entrance-lang-btn').on('click', function (e) {
                e.preventDefault();
                var lang = $(this).data('lang');
                var remember = $('#rmm-entrance-remember').is(':checked');

                if (remember) {
                    localStorage.setItem('rmm_lang_chosen', '1');
                    var date = new Date();
                    date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
                    document.cookie = 'rmm_lang_chosen=1; path=/; expires=' + date.toUTCString();
                } else {
                    localStorage.removeItem('rmm_lang_chosen');
                    document.cookie = 'rmm_lang_chosen=; path=/; expires=Thu, 01 Jan 1970 00:00:00 UTC;';
                    // Temporary flag for this tab session only so page redirect does not re-open modal
                    sessionStorage.setItem('rmm_lang_session_chosen', '1');
                }

                var dateLang = new Date();
                dateLang.setTime(dateLang.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = 'rmm_selected_lang=' + encodeURIComponent(lang) + '; path=/; expires=' + dateLang.toUTCString();

                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('lang', lang);
                window.location.href = currentUrl.toString();
            });
        }

        // =====================================================================
        // 8. Standalone Search Bar Filter Handler
        // =====================================================================
        $(document).on('input', '.rmm-standalone-search-input', function () {
            var val = $(this).val().toLowerCase().trim();
            var $wrap = $(this).closest('.rmm-standalone-search-wrap');
            var $clearBtn = $wrap.find('.rmm-search-clear-btn');

            if (val.length > 0) {
                $clearBtn.css('display', 'flex');
            } else {
                $clearBtn.hide();
            }

            $('.rmm-food-card').each(function () {
                var title = ($(this).attr('data-title') || '').toLowerCase();
                var text = $(this).text().toLowerCase();
                if (!val || title.indexOf(val) !== -1 || text.indexOf(val) !== -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

        $(document).on('click', '.rmm-search-clear-btn', function (e) {
            e.preventDefault();
            var $wrap = $(this).closest('.rmm-standalone-search-wrap');
            var $input = $wrap.find('.rmm-standalone-search-input');
            $input.val('').trigger('input').focus();
        });

        // =====================================================================
        // 9. Language Switcher Dropdown Interaction
        // =====================================================================
        $(document).on('click', '.rmm-lang-trigger-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $component = $(this).closest('.rmm-lang-dropdown-component');
            var isOpen = $component.hasClass('is-open');

            // Close any other open dropdowns
            $('.rmm-lang-dropdown-component').not($component).removeClass('is-open').find('.rmm-lang-trigger-btn').attr('aria-expanded', 'false');

            if (!isOpen) {
                $component.addClass('is-open');
                $(this).attr('aria-expanded', 'true');
            } else {
                $component.removeClass('is-open');
                $(this).attr('aria-expanded', 'false');
            }
        });

        // Close dropdown when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('.rmm-lang-dropdown-component').length) {
                $('.rmm-lang-dropdown-component').removeClass('is-open').find('.rmm-lang-trigger-btn').attr('aria-expanded', 'false');
            }
        });

        // Close dropdown on ESC
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('.rmm-lang-dropdown-component').removeClass('is-open').find('.rmm-lang-trigger-btn').attr('aria-expanded', 'false');
            }
        });

        // Language Selection via Dropdown Item
        $(document).on('click', '.rmm-lang-dropdown-item', function (e) {
            var $item = $(this);
            var lang = $item.data('lang');
            if ($item.hasClass('is-active')) {
                // Clicking on active lang simply closes dropdown
                e.preventDefault();
                $('.rmm-lang-dropdown-panel').hide();
                $('.rmm-lang-dropdown-component').removeClass('is-open');
                return;
            }

            if (lang) {
                // Set language cookie (30 days)
                var dateLang = new Date();
                dateLang.setTime(dateLang.getTime() + (30 * 24 * 60 * 60 * 1000));
                document.cookie = 'rmm_selected_lang=' + encodeURIComponent(lang) + '; path=/; expires=' + dateLang.toUTCString();
                document.cookie = 'rmm_lang_chosen=1; path=/; expires=' + dateLang.toUTCString();
                try {
                    localStorage.setItem('rmm_lang_chosen', '1');
                } catch (err) {}

                // If href is set, let browser navigate or redirect explicitly
                var targetUrl = $item.attr('href');
                if (!targetUrl || targetUrl === '#') {
                    e.preventDefault();
                    var url = new URL(window.location.href);
                    url.searchParams.set('lang', lang);
                    window.location.href = url.toString();
                }
            }
        });

        // Food Loop Widget Load More Handler
        $(document).on('click', '.rmm-food-loop-load-more-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            if ($btn.data('loading')) return;

            var $wrapper = $btn.closest('.rmm-food-loop-wrapper');
            var $grid = $wrapper.find('.rmm-food-loop-grid');
            var page = parseInt($btn.data('page') || 1, 10);
            var maxPages = parseInt($btn.data('max-pages') || 1, 10);
            var queryData = $btn.data('query') || {};

            var nextPage = page + 1;
            $btn.data('loading', true).addClass('is-loading');
            var originalText = $btn.html();
            $btn.html('<span>⏳ ' + (window.rmmConfig && window.rmmConfig.strings && window.rmmConfig.strings.loading ? window.rmmConfig.strings.loading : 'در حال بارگذاری...') + '</span>');

            $.ajax({
                url: (window.rmmConfig && window.rmmConfig.ajax_url) ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'rmm_load_more_loop_products',
                    page: nextPage,
                    query: queryData
                },
                success: function (res) {
                    if (res && res.success && res.data && res.data.html) {
                        var resolvedPage = parseInt(res.data.page || nextPage, 10);
                        $grid.append(res.data.html);
                        $btn.data('page', resolvedPage);

                        if (!res.data.has_more || resolvedPage >= maxPages) {
                            $btn.fadeOut(300, function () { $(this).remove(); });
                        } else {
                            $btn.html(originalText);
                        }
                    } else {
                        $btn.fadeOut(300, function () { $(this).remove(); });
                    }
                },
                error: function () {
                    $btn.html(originalText);
                },
                complete: function () {
                    $btn.data('loading', false).removeClass('is-loading');
                }
            });
        });

        // =====================================================================
        // 10. Catalog Mode (View-Only) Floating Selection List
        // =====================================================================
        function escHtml(str) {
            return String(str === null || str === undefined ? '' : str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function isViewOnly() {
            return !!(window.rmmConfig && window.rmmConfig.is_view_only);
        }

        function isCatalogPriceHidden() {
            return !!(window.rmmConfig && window.rmmConfig.catalog_hide_price);
        }

        function getCatalogStorageKey() {
            var branchId = (window.rmmConfig && window.rmmConfig.active_branch) ? window.rmmConfig.active_branch : '0';
            return 'rmm_catalog_list_' + branchId;
        }

        function loadCatalogList() {
            try {
                var raw = localStorage.getItem(getCatalogStorageKey());
                if (raw) {
                    var parsed = JSON.parse(raw);
                    if (Array.isArray(parsed)) return parsed;
                }
            } catch (err) {}
            return [];
        }

        function saveCatalogList(list) {
            try {
                localStorage.setItem(getCatalogStorageKey(), JSON.stringify(list));
            } catch (err) {}
        }

        var catalogList = loadCatalogList();

        function getCurrencyLabel() {
            return (window.rmmConfig && window.rmmConfig.currency_symbol) ? window.rmmConfig.currency_symbol : 'تومان';
        }

        function formatCatalogPrice(amount) {
            if (isCatalogPriceHidden()) {
                return t('digital_menu', 'منو دیجیتال');
            }
            return formatPriceNumber(amount) + ' ' + getCurrencyLabel();
        }

        function ensureCatalogFloatingBar() {
            if (!isViewOnly()) return $();
            var $bar = $('#rmm-catalog-floating-bar');
            if (!$bar.length && ($('.rmm-catalog-add-btn').length || $('.rmm-menu-widget-wrapper').length || $('.rmm-food-card').length || catalogList.length > 0)) {
                $('body').append(
                    '<div id="rmm-catalog-floating-bar" class="rmm-floating-cart-wrapper rmm-catalog-floating-wrapper rmm-hidden-cart">' +
                    '  <button type="button" class="rmm-floating-cart-btn rmm-catalog-floating-btn" id="rmm-catalog-open-drawer-btn" aria-haspopup="dialog">' +
                    '    <div class="rmm-cart-left">' +
                    '      <span class="rmm-cart-badge-count" id="rmm-catalog-count" data-count="0">0</span>' +
                    '      <span class="rmm-cart-label">' + escHtml(t('my_selection_list', 'لیست انتخاب‌های من')) + '</span>' +
                    '    </div>' +
                    '    <div class="rmm-cart-right">' +
                    '      <span class="rmm-cart-total" id="rmm-catalog-total">0</span>' +
                    '      <span class="rmm-cart-arrow">&#9650;</span>' +
                    '    </div>' +
                    '  </button>' +
                    '</div>'
                );
                $bar = $('#rmm-catalog-floating-bar');
            }
            return $bar;
        }

        function ensureCatalogDrawer() {
            var $drawer = $('#rmm-catalog-drawer-backdrop');
            if (!$drawer.length) {
                var whatsappNum = (window.rmmConfig && window.rmmConfig.whatsapp_number) ? String(window.rmmConfig.whatsapp_number).trim() : '';
                var waBtnHtml = whatsappNum
                    ? '<a href="#" target="_blank" class="rmm-catalog-wa-btn" id="rmm-catalog-wa-send-btn">💬 ' + escHtml(t('send_list_whatsapp', 'ارسال لیست در واتساپ')) + '</a>'
                    : '';

                $('body').append(
                    '<div id="rmm-catalog-drawer-backdrop" class="rmm-catalog-drawer-backdrop" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="rmm-catalog-drawer-title">' +
                    '  <div class="rmm-catalog-drawer">' +
                    '    <div class="rmm-catalog-drawer-header">' +
                    '      <div class="rmm-catalog-drawer-title-wrap">' +
                    '        <h3 id="rmm-catalog-drawer-title" class="rmm-catalog-drawer-title">📝 ' + escHtml(t('my_selection_list', 'لیست انتخاب‌های من')) + '</h3>' +
                    '        <span class="rmm-catalog-drawer-count" id="rmm-catalog-drawer-count">0 ' + escHtml(t('catalog_items_unit', 'مورد')) + '</span>' +
                    '      </div>' +
                    '      <div class="rmm-catalog-drawer-header-actions">' +
                    '        <button type="button" class="rmm-catalog-clear-btn" id="rmm-catalog-clear-btn" title="' + escHtml(t('clear_list_title', 'پاک کردن لیست')) + '">🗑️ ' + escHtml(t('clear_list', 'پاک کردن')) + '</button>' +
                    '        <button type="button" class="rmm-catalog-close-btn" id="rmm-catalog-close-btn" aria-label="' + escHtml(t('close', 'بستن')) + '">&times;</button>' +
                    '      </div>' +
                    '    </div>' +
                    '    <div class="rmm-catalog-drawer-body" id="rmm-catalog-drawer-list"></div>' +
                    '    <div class="rmm-catalog-drawer-footer">' +
                    '      <div class="rmm-catalog-summary-row">' +
                    '        <span class="rmm-catalog-summary-label">' + escHtml(t('total_selections_label', 'تعداد کل انتخاب‌ها:')) + '</span>' +
                    '        <strong class="rmm-catalog-summary-qty" id="rmm-catalog-footer-qty">0 ' + escHtml(t('catalog_qty_unit', 'عدد')) + '</strong>' +
                    '      </div>' +
                    '      <div class="rmm-catalog-grand-total-row">' +
                    '        <span class="rmm-catalog-grand-label">' + escHtml(t('grand_total_label', 'جمع کل:')) + '</span>' +
                    '        <strong class="rmm-catalog-grand-amount" id="rmm-catalog-footer-total">0</strong>' +
                    '      </div>' +
                    '      <div class="rmm-catalog-footer-actions">' +
                    waBtnHtml +
                    '        <button type="button" class="rmm-catalog-done-btn" id="rmm-catalog-done-btn">' + escHtml(t('continue_viewing_menu', 'ادامه مشاهده منو')) + '</button>' +
                    '      </div>' +
                    '    </div>' +
                    '  </div>' +
                    '</div>'
                );
                $drawer = $('#rmm-catalog-drawer-backdrop');
            }
            return $drawer;
        }

        function getCatalogTotals() {
            var totalQty = 0;
            var totalAmount = 0;
            catalogList.forEach(function (item) {
                var q = parseInt(item.qty, 10) || 0;
                var p = parseFloat(item.price) || 0;
                totalQty += q;
                totalAmount += (q * p);
            });
            return { qty: totalQty, amount: totalAmount };
        }

        function renderCatalogFloatingBar() {
            if (!isViewOnly()) return;
            var $bar = ensureCatalogFloatingBar();
            if (!$bar.length) return;

            var totals = getCatalogTotals();
            var $countEl = $('#rmm-catalog-count');
            var $totalEl = $('#rmm-catalog-total');

            $countEl.attr('data-count', totals.qty).text(totals.qty);
            if (isCatalogPriceHidden()) {
                $totalEl.text(totals.qty + ' ' + t('catalog_items_unit', 'مورد'));
            } else {
                $totalEl.text(formatCatalogPrice(totals.amount));
            }

            if (totals.qty > 0) {
                $bar.removeClass('rmm-hidden-cart');
            } else {
                $bar.addClass('rmm-hidden-cart');
            }
        }

        function syncCatalogCardButtons() {
            if (!isViewOnly()) return;
            var qtyById = {};
            catalogList.forEach(function (item) {
                var idKey = String(item.id);
                qtyById[idKey] = (qtyById[idKey] || 0) + (parseInt(item.qty, 10) || 0);
            });

            $('.rmm-catalog-add-btn').each(function () {
                var $btn = $(this);
                var pid = String($btn.attr('data-id') || '');
                var count = qtyById[pid] || 0;
                if (count > 0) {
                    $btn.addClass('in-list').text('✓ ' + t('in_list', 'در لیست') + ' (' + count + ') +');
                } else {
                    $btn.removeClass('in-list').text('+ ' + t('add_to_list', 'افزودن به لیست'));
                }
            });
        }

        function renderCatalogDrawerList() {
            var $drawer = ensureCatalogDrawer();
            var $listEl = $drawer.find('#rmm-catalog-drawer-list');
            var totals = getCatalogTotals();

            $drawer.find('#rmm-catalog-drawer-count').text(totals.qty + ' ' + t('catalog_items_unit', 'مورد'));
            $drawer.find('#rmm-catalog-footer-qty').text(totals.qty + ' ' + t('catalog_qty_unit', 'عدد'));
            $drawer.find('#rmm-catalog-footer-total').text(formatCatalogPrice(totals.amount));

            if (!catalogList.length) {
                $listEl.html(
                    '<div class="rmm-catalog-empty">' +
                    '  <span class="rmm-catalog-empty-icon">🍽️</span>' +
                    '  <p>' + escHtml(t('empty_list_title', 'هنوز غذایی به لیست انتخاب شما اضافه نشده است.')) + '</p>' +
                    '  <span>' + escHtml(t('empty_list_hint', 'از منو روی دکمه «+ افزودن به لیست» کلیک کنید.')) + '</span>' +
                    '</div>'
                );
                $drawer.find('#rmm-catalog-clear-btn').hide();
                $drawer.find('#rmm-catalog-wa-send-btn').hide();
                return;
            }

            $drawer.find('#rmm-catalog-clear-btn').show();

            var html = '';
            var waLines = [t('wa_greeting', 'سلام، لیست غذاهای انتخابی من:')];
            catalogList.forEach(function (item, idx) {
                var q = parseInt(item.qty, 10) || 1;
                var p = parseFloat(item.price) || 0;
                var lineTotal = q * p;
                var thumb = item.image ? '<img src="' + escHtml(item.image) + '" class="rmm-catalog-item-thumb" alt="' + escHtml(item.title) + '">' : '<div class="rmm-catalog-item-thumb rmm-catalog-thumb-placeholder">🍽️</div>';
                var metaHtml = item.meta ? '<div class="rmm-catalog-item-meta">' + escHtml(item.meta) + '</div>' : '';

                html += '<div class="rmm-catalog-item-card" data-index="' + idx + '">' +
                    thumb +
                    '<div class="rmm-catalog-item-body">' +
                    '  <div class="rmm-catalog-item-top">' +
                    '    <h4 class="rmm-catalog-item-title">' + escHtml(item.title) + '</h4>' +
                    '    <button type="button" class="rmm-catalog-item-remove" data-index="' + idx + '" title="' + escHtml(t('remove', 'حذف')) + '">&times;</button>' +
                    '  </div>' +
                    metaHtml +
                    '  <div class="rmm-catalog-item-unit-price">' + escHtml(t('unit_price_label', 'قیمت واحد:')) + ' ' + escHtml(formatCatalogPrice(p)) + '</div>' +
                    '  <div class="rmm-catalog-item-bottom">' +
                    '    <div class="rmm-catalog-item-qty">' +
                    '      <button type="button" class="rmm-cat-qty-btn rmm-cat-qty-plus" data-index="' + idx + '" aria-label="' + escHtml(t('increase_qty', 'افزایش تعداد')) + '">+</button>' +
                    '      <span class="rmm-cat-qty-num">' + q + '</span>' +
                    '      <button type="button" class="rmm-cat-qty-btn rmm-cat-qty-minus" data-index="' + idx + '" aria-label="' + escHtml(t('decrease_qty', 'کاهش تعداد')) + '">−</button>' +
                    '    </div>' +
                    '    <div class="rmm-catalog-item-subtotal">' +
                    '      <span class="rmm-catalog-sub-label">' + escHtml(t('line_amount_label', 'مبلغ:')) + '</span> ' +
                    '      <strong class="rmm-catalog-sub-val">' + escHtml(formatCatalogPrice(lineTotal)) + '</strong>' +
                    '    </div>' +
                    '  </div>' +
                    '</div>' +
                    '</div>';

                waLines.push((idx + 1) + '. ' + item.title + (item.meta ? ' (' + item.meta + ')' : '') + ' × ' + q + (isCatalogPriceHidden() ? '' : ' — ' + formatCatalogPrice(lineTotal)));
            });

            if (!isCatalogPriceHidden()) {
                waLines.push(t('grand_total_label', 'جمع کل:') + ' ' + formatCatalogPrice(totals.amount));
            }

            var whatsappNum = (window.rmmConfig && window.rmmConfig.whatsapp_number) ? String(window.rmmConfig.whatsapp_number).trim() : '';
            if (whatsappNum) {
                $drawer.find('#rmm-catalog-wa-send-btn')
                    .attr('href', 'https://wa.me/' + encodeURIComponent(whatsappNum) + '?text=' + encodeURIComponent(waLines.join('\n')))
                    .show();
            }

            $listEl.html(html);
        }

        function addToCatalogList(item, $triggerBtn) {
            if (!item || !item.id) return;
            var key = String(item.id) + '|' + String(item.meta || '');
            var qtyToAdd = parseInt(item.qty, 10) || 1;
            var existing = null;
            for (var i = 0; i < catalogList.length; i++) {
                if (catalogList[i].key === key) {
                    existing = catalogList[i];
                    break;
                }
            }
            if (existing) {
                existing.qty = (parseInt(existing.qty, 10) || 0) + qtyToAdd;
                if (item.price > 0) existing.price = item.price;
                if (item.image) existing.image = item.image;
            } else {
                catalogList.push({
                    key: key,
                    id: item.id,
                    title: item.title || t('default_food_name', 'غذا'),
                    price: parseFloat(item.price) || 0,
                    image: item.image || '',
                    qty: qtyToAdd,
                    meta: item.meta || ''
                });
            }

            saveCatalogList(catalogList);
            renderCatalogFloatingBar();
            syncCatalogCardButtons();

            var $barBtn = $('#rmm-catalog-floating-bar .rmm-floating-cart-btn');
            $barBtn.removeClass('rmm-pulse-pop');
            void ($barBtn[0] && $barBtn[0].offsetWidth);
            $barBtn.addClass('rmm-pulse-pop');

            if ($('#rmm-catalog-drawer-backdrop').is(':visible')) {
                renderCatalogDrawerList();
            }

            if ($triggerBtn && $triggerBtn.length && !$triggerBtn.hasClass('rmm-catalog-add-btn')) {
                var orig = $triggerBtn.data('rmm-orig-html') || $triggerBtn.html();
                $triggerBtn.data('rmm-orig-html', orig);
                $triggerBtn.text('✓ ' + t('added_to_list', 'به لیست اضافه شد'));
                setTimeout(function () {
                    $triggerBtn.html(orig);
                }, 1100);
            }
        }

        if (isViewOnly()) {
            ensureCatalogFloatingBar();
            renderCatalogFloatingBar();
            syncCatalogCardButtons();
        }

        // Card "+ افزودن به لیست" button click
        $(document).on('click', '.rmm-catalog-add-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $btn = $(this);
            var id = parseInt($btn.attr('data-id'), 10);
            var title = $btn.attr('data-title') || $btn.closest('.rmm-food-card').find('.rmm-food-title').first().text().trim();
            var price = parseFloat($btn.attr('data-price')) || 0;
            var image = $btn.attr('data-image') || $btn.closest('.rmm-food-card').find('img.rmm-card-img').first().attr('src') || '';

            addToCatalogList({
                id: id,
                title: title,
                price: price,
                image: image,
                qty: 1,
                meta: ''
            }, $btn);
        });

        // Modal "+ افزودن به لیست انتخاب" button click (in view_only mode)
        $(document).on('click', '#rmm-modal-catalog-add-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var $form = $('#rmm-modal-add-form');
            var id = parseInt($btn.attr('data-id') || $form.data('product-id'), 10);
            var title = $btn.attr('data-title') || $('.rmm-modal-food-title').first().text().trim();
            var image = $btn.attr('data-image') || $('.rmm-modal-hero-img').first().attr('src') || '';
            var qty = parseInt($form.find('.rmm-qty-val').val(), 10) || 1;

            var unitPrice = parseFloat($form.data('base-price')) || 0;
            var metaParts = [];
            var $portion = $form.find('input[name="rmm_portion"]:checked');
            if ($portion.length) {
                unitPrice = parseFloat($portion.data('price')) || 0;
                var portionName = $portion.closest('label').text().trim();
                if (portionName) metaParts.push(portionName);
            }
            $form.find('.rmm-modal-addon-item input:checked').each(function () {
                unitPrice += parseFloat($(this).data('price')) || 0;
                try {
                    var parsed = JSON.parse($(this).val());
                    if (parsed && parsed.name) metaParts.push(parsed.name);
                } catch (err) {}
            });

            if (isCatalogPriceHidden() || $btn.attr('data-hide-price') === '1') {
                unitPrice = 0;
            }

            addToCatalogList({
                id: id,
                title: title,
                price: unitPrice,
                image: image,
                qty: qty,
                meta: metaParts.join('، ')
            }, $btn);

            setTimeout(function () {
                $('#rmm-modifiers-modal').fadeOut(200);
                $('body, html').removeClass('rmm-modal-open');
            }, 450);
        });

        // Open Catalog Drawer when clicking floating bar
        $(document).on('click', '#rmm-catalog-open-drawer-btn, #rmm-catalog-floating-bar .rmm-floating-cart-btn', function (e) {
            e.preventDefault();
            renderCatalogDrawerList();
            $('#rmm-catalog-drawer-backdrop').fadeIn(200);
            $('body, html').addClass('rmm-modal-open');
        });

        // Close Catalog Drawer
        $(document).on('click', '#rmm-catalog-close-btn, #rmm-catalog-done-btn, #rmm-catalog-drawer-backdrop', function (e) {
            if (e.target === this || $(this).is('#rmm-catalog-close-btn, #rmm-catalog-done-btn')) {
                $('#rmm-catalog-drawer-backdrop').fadeOut(200);
                $('body, html').removeClass('rmm-modal-open');
            }
        });

        // Drawer quantity +/- / remove / clear
        $(document).on('click', '.rmm-cat-qty-plus', function () {
            var idx = parseInt($(this).attr('data-index'), 10);
            if (!isNaN(idx) && catalogList[idx]) {
                catalogList[idx].qty = (parseInt(catalogList[idx].qty, 10) || 1) + 1;
                saveCatalogList(catalogList);
                renderCatalogFloatingBar();
                syncCatalogCardButtons();
                renderCatalogDrawerList();
            }
        });

        $(document).on('click', '.rmm-cat-qty-minus', function () {
            var idx = parseInt($(this).attr('data-index'), 10);
            if (!isNaN(idx) && catalogList[idx]) {
                var cur = parseInt(catalogList[idx].qty, 10) || 1;
                if (cur > 1) {
                    catalogList[idx].qty = cur - 1;
                } else {
                    catalogList.splice(idx, 1);
                }
                saveCatalogList(catalogList);
                renderCatalogFloatingBar();
                syncCatalogCardButtons();
                renderCatalogDrawerList();
            }
        });

        $(document).on('click', '.rmm-catalog-item-remove', function () {
            var idx = parseInt($(this).attr('data-index'), 10);
            if (!isNaN(idx) && catalogList[idx]) {
                catalogList.splice(idx, 1);
                saveCatalogList(catalogList);
                renderCatalogFloatingBar();
                syncCatalogCardButtons();
                renderCatalogDrawerList();
            }
        });

        $(document).on('click', '#rmm-catalog-clear-btn', function () {
            catalogList = [];
            saveCatalogList(catalogList);
            renderCatalogFloatingBar();
            syncCatalogCardButtons();
            renderCatalogDrawerList();
        });

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                if ($('#rmm-catalog-drawer-backdrop').is(':visible')) {
                    $('#rmm-catalog-drawer-backdrop').fadeOut(200);
                    $('body, html').removeClass('rmm-modal-open');
                }
            }
        });

    });

})(jQuery);
