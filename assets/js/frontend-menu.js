/**
 * TastyMenu Pro - Frontend Menu Interactions
 */

(function ($) {
    'use strict';

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
        var floatingCart = $('#rmm-floating-cart-bar');
        var cartCount = $('#rmm-cart-count');
        var cartTotal = $('#rmm-cart-total');

        // Lightbox state variables
        var activeModalImages = [];
        var activeLightboxIndex = 0;

        function ensureModalContainer() {
            var $m = $('#rmm-modifiers-modal');
            if (!$m.length) {
                $('body').append(
                    '<div id="rmm-modifiers-modal" class="rmm-modal-backdrop" style="display:none;">' +
                    '    <div class="rmm-modal-box">' +
                    '        <button class="rmm-modal-close-btn" type="button" aria-label="Close">&times;</button>' +
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

        // 2. Category Nav Pills Filter (No Page Scroll, Pure Instant Filter)
        $('.rmm-cat-pill').on('click', function (e) {
            e.preventDefault();
            $('.rmm-cat-pill').removeClass('active');
            $(this).addClass('active');

            var catId = $(this).data('cat-id') || $(this).data('category');

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
                        // If card is inside a hidden category section, it is already hidden
                        if (!$(this).closest('.rmm-category-section').length) {
                            $(this).hide();
                        }
                    }
                });
            }
        });

        // 3. Quick Add to Cart (No Options)
        $(document).on('click', '.rmm-quick-add-btn', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var productId = $btn.data('id');

            $btn.prop('disabled', true).text('⏳...');

            $.ajax({
                url: window.rmmConfig ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'woocommerce_add_to_cart',
                    product_id: productId,
                    quantity: 1
                },
                success: function (res) {
                    $btn.prop('disabled', false).text('✓ اضافه شد');
                    setTimeout(function () {
                        $btn.text('+ افزودن به سبد');
                    }, 1500);

                    // Refresh cart fragments
                    $(document.body).trigger('wc_fragment_refresh');
                    updateFloatingCart();
                },
                error: function () {
                    $btn.prop('disabled', false).text('+ افزودن به سبد');
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
            var pid = $(this).data('product-id');
            if (!pid) return;

            var $btn = $(this);
            $btn.prop('disabled', true);

            $.ajax({
                url: window.rmmConfig ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'woocommerce_add_to_cart',
                    product_id: pid,
                    quantity: 1
                },
                success: function (res) {
                    $btn.prop('disabled', false);
                    $(document.body).trigger('wc_fragment_refresh');
                    updateFloatingCart();
                },
                error: function () {
                    $btn.prop('disabled', false);
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
            loadDefaultModal(pid, $card);
        });

        // 4. Open Food Details / Modifiers Modal (on Card Click or Button Click)
        $(document).on('click', '.rmm-food-card.rmm-card-clickable, .rmm-open-modifiers-btn', function (e) {
            // Ignore click if user clicked directly on catalog WhatsApp/call button or quick-add
            if ($(e.target).closest('.rmm-quick-add-btn, .rmm-btn-order.whatsapp, .rmm-btn-order.call').length) {
                return;
            }
            e.preventDefault();

            var $card = $(this).closest('.rmm-food-card');
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
                modalContent.html('<div class="rmm-modal-loading-box"><span class="rmm-modal-spinner">⏳</span><p>در حال بارگذاری قالب المنتور...</p></div>');
                modal.fadeIn(200);
                $('body, html').addClass('rmm-modal-open');

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
                            modalContent.html('<button class="rmm-modal-close-btn" type="button" aria-label="Close">&times;</button><div class="rmm-custom-template-wrap">' + res.data.html + '</div>');
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
                $curModal.fadeIn(150);
                $('body, html').addClass('rmm-modal-open');
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
                    '<button class="rmm-modal-close-btn" type="button" aria-label="' + t('close', 'بستن') + '">&times;</button>' +
                    (cardImg ? '<img src="' + cardImg + '" class="rmm-modal-hero-img" alt="' + cardTitle + '">' : '') +
                    '</div>' +
                    '<div class="rmm-modal-body">' +
                    '<div class="rmm-modal-title-row"><h3 class="rmm-modal-food-title">' + cardTitle + '</h3></div>' +
                    '<div class="rmm-modal-price-row"><div class="rmm-modal-food-price">' + cardPriceHtml + '</div></div>' +
                    (cardDesc ? '<div class="rmm-modal-desc-box"><p class="rmm-modal-desc-text">' + cardDesc + '</p></div>' : '') +
                    (cardIngredients ? '<div class="rmm-modal-ingredients-box"><p class="rmm-modal-ingredients-text">' + cardIngredients + '</p></div>' : '') +
                    '<div class="rmm-modal-loading-box" style="padding:15px 0;"><span class="rmm-modal-spinner">🍲</span><p style="margin:0; font-size:12px; font-weight:600; color:#64748b;">' + t('loading', 'در حال دریافت گزینه‌ها...') + '</p></div>' +
                    '</div>';

                $curContent.html(optHtml);
            } else {
                $curContent.html('<div class="rmm-modal-loading-box"><span class="rmm-modal-spinner">🍲</span><p style="margin:0; font-weight:600; color:#475569;">' + t('loading', 'در حال بارگذاری جزئیات غذا...') + '</p></div>');
            }

            $curModal.fadeIn(150);
            $('body, html').addClass('rmm-modal-open');

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
                            $curContent.html('<button class="rmm-modal-close-btn" type="button" aria-label="Close">&times;</button><p style="color:#ef4444; text-align:center; padding:40px 20px;">' + errMsg + '</p>');
                        }
                    } catch (renderErr) {
                        console.error('Error rendering food modal:', renderErr);
                        $curContent.html('<button class="rmm-modal-close-btn" type="button" aria-label="Close">&times;</button><p style="color:#ef4444; text-align:center; padding:40px 20px;">' + t('error_loading', 'خطا در نمایش اطلاعات غذا.') + '</p>');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error loading food modal:', status, error);
                    $curContent.html('<button class="rmm-modal-close-btn" type="button" aria-label="Close">&times;</button><p style="color:#ef4444; text-align:center; padding:40px 20px;">' + t('error_server', 'خطای ارتباط با سرور.') + '</p>');
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
                        '<img src="' + img + '" class="rmm-modal-hero-img" alt="' + data.name + '">' +
                        '</div>';
                    dotsHtml += '<span class="rmm-slider-dot' + activeClass + '" data-index="' + idx + '"></span>';
                });

                html += '<div class="rmm-modal-hero-wrap rmm-modal-slider-wrap" data-current-slide="0" data-total-slides="' + data.images.length + '">' +
                    '<button class="rmm-modal-close-btn" type="button" aria-label="' + t('close', 'بستن') + '">&times;</button>' +
                    '<div class="rmm-modal-slider-viewport">' +
                    '<div class="rmm-modal-slider-track">' + slidesHtml + '</div>' +
                    '</div>' +
                    '<button type="button" class="rmm-slider-arrow rmm-slider-prev" aria-label="قبلی">&#10094;</button>' +
                    '<button type="button" class="rmm-slider-arrow rmm-slider-next" aria-label="بعدی">&#10095;</button>' +
                    '<div class="rmm-slider-dots">' + dotsHtml + '</div>' +
                    '<span class="rmm-slider-counter"><span class="rmm-slide-current">1</span> / <span class="rmm-slide-total">' + data.images.length + '</span></span>' +
                    '<div class="rmm-modal-zoom-hint">🔍 ' + t('view_image', 'مشاهده بزرگ‌تر') + '</div>' +
                    (data.badge ? '<span class="rmm-modal-badge-overlay ' + (data.badge.class || '') + '">' + (data.badge.image ? '<img src="' + data.badge.image + '" class="rmm-badge-icon-img" alt=""> ' : (data.badge.icon ? data.badge.icon + ' ' : '')) + (data.badge.label || '') + '</span>' : '') +
                    '</div>';
            } else if (data.image) {
                html += '<div class="rmm-modal-hero-wrap">' +
                    '<button class="rmm-modal-close-btn" type="button" aria-label="' + t('close', 'بستن') + '">&times;</button>' +
                    '<img src="' + data.image + '" class="rmm-modal-hero-img" alt="' + data.name + '">' +
                    '<div class="rmm-modal-zoom-hint">🔍 ' + t('view_image', 'مشاهده بزرگ‌تر') + '</div>' +
                    (data.badge ? '<span class="rmm-modal-badge-overlay ' + (data.badge.class || '') + '">' + (data.badge.image ? '<img src="' + data.badge.image + '" class="rmm-badge-icon-img" alt=""> ' : (data.badge.icon ? data.badge.icon + ' ' : '')) + (data.badge.label || '') + '</span>' : '') +
                    '</div>';
            } else {
                html += '<div style="position:relative; padding-top:20px; padding-left:20px;">' +
                    '<button class="rmm-modal-close-btn" type="button" aria-label="' + t('close', 'بستن') + '" style="top:14px; left:14px;">&times;</button>' +
                    '</div>';
            }

            // 2. Modal Body
            html += '<div class="rmm-modal-body">';

            // Title & Price Row (Proper strikethrough sale price vs regular)
            var priceMarkup = '';
            if (data.catalog_hide_price && data.is_view_only) {
                priceMarkup = '<span class="rmm-price-hidden">' + t('digital_menu', 'منو دیجیتال') + '</span>';
            } else if (data.is_on_sale && data.formatted_regular) {
                priceMarkup = '<div class="rmm-modal-price-wrap"><del class="rmm-modal-old-price">' + data.formatted_regular + '</del> <ins class="rmm-modal-sale-price">' + data.formatted_price + '</ins></div>';
            } else {
                priceMarkup = '<span class="rmm-modal-regular-price">' + data.formatted_price + '</span>';
            }

            // 1. Food Name Row (Full Width Line)
            html += '<div class="rmm-modal-title-row">' +
                '<h3 class="rmm-modal-food-title">' + data.name + '</h3>' +
                '</div>';

            // 2. Food Price Row (Full Width Line Below Name)
            html += '<div class="rmm-modal-price-row">' +
                '<div class="rmm-modal-food-price" id="rmm-calc-price">' + priceMarkup + '</div>' +
                '</div>';

            // 3. Chips Row (Badges, Features, Prep Time, Calories)
            var chips = [];
            if (data.badge && (data.badge.label || data.badge.image)) {
                var bImg = data.badge.image ? '<img src="' + data.badge.image + '" class="rmm-badge-icon-img" alt=""> ' : '';
                var bIcon = (!data.badge.image && data.badge.icon) ? data.badge.icon + ' ' : '';
                chips.push('<span class="rmm-modal-chip ' + (data.badge.class || '') + '">' + bImg + bIcon + (data.badge.label || '') + '</span>');
            }
            if (data.prep_time) {
                chips.push('<span class="rmm-modal-chip">⏱️ ' + data.prep_time + ' ' + t('minutes', 'دقیقه') + '</span>');
            }
            if (data.calories) {
                chips.push('<span class="rmm-modal-chip calories">🔥 ' + t('calories_label', 'ارزش غذایی و کالری:') + ' ' + data.calories + ' ' + t('kcal', 'کالری') + '</span>');
            }
            if (chips.length) {
                html += '<div class="rmm-modal-chips-row">' + chips.join('') + '</div>';
            }

            // 4. Description Box (توضیحات محصول)
            if (data.description) {
                html += '<div class="rmm-modal-desc-box">' +
                    '<p class="rmm-modal-desc-text">' + data.description + '</p>' +
                    '</div>';
            }

            // 5. Ingredients Box (موارد تشکیل‌دهنده و ترکیبات)
            if (data.ingredients) {
                html += '<div class="rmm-modal-ingredients-box">' +
                    '<h4>🥗 ' + t('ingredients', 'موارد تشکیل‌دهنده و ترکیبات:') + '</h4>' +
                    '<p class="rmm-modal-ingredients-text">' + data.ingredients + '</p>' +
                    '</div>';
            }

            // Form container
            html += '<form id="rmm-modal-add-form" data-product-id="' + data.id + '" data-base-price="' + data.base_price + '" data-currency="' + data.currency_symbol + '">';

            // Portions / Sizes
            if (data.portions && data.portions.length > 0) {
                html += '<div class="rmm-modal-section-box">' +
                    '<span class="rmm-modal-section-title">📏 ' + t('portions', 'انتخاب سایز یا پرس:') + '</span>';
                data.portions.forEach(function (port, idx) {
                    var isChecked = (idx === 0) ? 'checked' : '';
                    var isSelected = (idx === 0) ? 'is-selected' : '';
                    html += '<label class="rmm-modal-portion-item ' + isSelected + '">' +
                        '<span><input type="radio" name="rmm_portion" value="' + idx + '" data-price="' + port.price + '" ' + isChecked + '> ' + port.name + '</span>' +
                        '<span class="rmm-modal-addon-price">' + formatPriceNumber(port.price) + ' ' + data.currency_symbol + '</span>' +
                        '</label>';
                });
                html += '</div>';
            }

            // Modifier Groups / Addons
            if (data.modifier_groups && data.modifier_groups.length > 0) {
                data.modifier_groups.forEach(function (group, gIdx) {
                    html += '<div class="rmm-modal-section-box">' +
                        '<span class="rmm-modal-section-title">🥗 ' + group.title + ':</span>';

                    if (group.options && group.options.length > 0) {
                        group.options.forEach(function (opt) {
                            var inputType = (group.type === 'radio') ? 'radio' : 'checkbox';
                            var inputName = (group.type === 'radio') ? 'rmm_radio_addon_' + gIdx : 'rmm_addons[]';
                            var optJson = JSON.stringify({ name: opt.name, price: opt.price, group: group.title });
                            var priceTag = (opt.price > 0) ? '(+' + formatPriceNumber(opt.price) + ' ' + data.currency_symbol + ')' : '(' + t('free', 'رایگان') + ')';

                            html += '<label class="rmm-modal-addon-item">' +
                                '<span><input type="' + inputType + '" name="' + inputName + '" value=\'' + optJson + '\' data-price="' + opt.price + '"> ' + opt.name + '</span>' +
                                '<span class="rmm-modal-addon-price">' + priceTag + '</span>' +
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
                    '<span class="rmm-modal-qty-label">' + t('quantity', 'تعداد سفارش:') + '</span>' +
                    '<div class="rmm-qty-controls">' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-minus">−</button>' +
                    '<input type="number" class="rmm-qty-val" name="quantity" value="1" min="1" max="99" readonly>' +
                    '<button type="button" class="rmm-qty-btn rmm-qty-plus">+</button>' +
                    '</div>' +
                    '</div>';

                // Kitchen Note
                html += '<div class="rmm-modal-kitchen-note" style="margin-bottom:14px;">' +
                    '<input type="text" name="rmm_order_notes" placeholder="' + t('kitchen_note_placeholder', 'یادداشت برای آشپزخانه (مثلاً: بدون پیاز، سس جداگانه...)') + '">' +
                    '</div>';

                // Submit Button
                html += '<div class="rmm-modal-submit-action">' +
                    '<button type="submit" class="rmm-modal-btn-submit" id="rmm-modal-submit-btn">' +
                    '<span>+ ' + t('add_to_cart', 'افزودن به سبد خرید') + '</span>' +
                    '<span id="rmm-modal-btn-total-price">' + formatPriceNumber(data.base_price) + ' ' + data.currency_symbol + '</span>' +
                    '</button>' +
                    '</div>';
            } else {
                // View-Only Catalog Action Button
                html += '<div class="rmm-modal-submit-action">';
                if (data.action_btn === 'whatsapp' && data.whatsapp_url) {
                    html += '<a href="' + data.whatsapp_url + '" target="_blank" class="rmm-modal-btn-submit" style="background:#25d366; text-decoration:none; justify-content:center; gap:8px;">' +
                        '💬 ' + t('order_whatsapp', 'سفارش و استعلام در واتساپ') +
                        '</a>';
                } else if (data.action_btn === 'call' && data.phone_number) {
                    html += '<a href="tel:' + data.phone_number + '" class="rmm-modal-btn-submit" style="background:#0284c7; text-decoration:none; justify-content:center; gap:8px;">' +
                        '📞 ' + t('call_order', 'تماس تلفنی با رستوران') +
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

            $btn.prop('disabled', true).text(t('submitting', 'در حال ثبت سفارش...'));

            var formData = $form.serializeArray();
            var postData = {
                action: 'woocommerce_add_to_cart',
                product_id: pId,
                quantity: 1
            };

            formData.forEach(function (item) {
                if (item.name === 'rmm_addons[]' || item.name.indexOf('rmm_radio_addon_') === 0) {
                    if (!postData['rmm_addons']) postData['rmm_addons'] = [];
                    postData['rmm_addons'].push(item.value);
                } else {
                    postData[item.name] = item.value;
                }
            });

            $.ajax({
                url: window.rmmConfig ? window.rmmConfig.ajax_url : '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: postData,
                success: function () {
                    $btn.text('✓ ' + t('added_to_cart', 'به سبد اضافه شد'));
                    setTimeout(function () {
                        modal.fadeOut(200);
                        $('body, html').removeClass('rmm-modal-open');
                        $btn.prop('disabled', false).text(t('add_to_cart', 'افزودن به سبد خرید'));
                    }, 700);

                    $(document.body).trigger('wc_fragment_refresh');
                    updateFloatingCart();
                },
                error: function () {
                    $btn.prop('disabled', false).text(t('error_adding', 'خطا در افزودن!'));
                }
            });
        });

        // Delegated Close Modal Handler (works for dynamic buttons, backdrop & icons)
        $(document).on('click', '.rmm-modal-close-btn, #rmm-modal-close-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#rmm-modifiers-modal, .rmm-modal-backdrop').fadeOut(200);
            $('body, html').removeClass('rmm-modal-open');
        });

        $(document).on('click', '#rmm-modifiers-modal, .rmm-modal-backdrop', function (e) {
            if (e.target === this) {
                $(this).fadeOut(200);
                $('body, html').removeClass('rmm-modal-open');
            }
        });

        // Close all modals on ESC key
        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('#rmm-modifiers-modal, .rmm-modal-backdrop').fadeOut(200);
                $('#rmm-lightbox-modal').fadeOut(200);
                if (entranceModal && entranceModal.length) entranceModal.fadeOut(200);
                $('body, html').removeClass('rmm-modal-open');
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
            var view = $btn.data('view');
            var $container = $btn.closest('.rmm-menu-widget-wrapper').find('.rmm-menu-container');

            $btn.siblings().removeClass('is-active');
            $btn.addClass('is-active');

            if (view === 'list') {
                $container.removeClass('rmm-view-grid').addClass('rmm-view-list');
            } else {
                $container.removeClass('rmm-view-list').addClass('rmm-view-grid');
            }

            try {
                localStorage.setItem('rmm_user_view_pref', view);
            } catch (err) {}
        });

        // Restore customer preference on load
        try {
            var savedView = localStorage.getItem('rmm_user_view_pref');
            if (savedView) {
                $('.rmm-view-btn[data-view="' + savedView + '"]').trigger('click');
            }
        } catch (err) {}

        // Update floating cart state
        function updateFloatingCart() {
            if (!floatingCart.length) return;
            floatingCart.removeClass('rmm-hidden-cart');
        }

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
            var nonceVal = $btn.data('nonce') || (window.rmmConfig && window.rmmConfig.nonce ? window.rmmConfig.nonce : '');
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
                    security: nonceVal,
                    query: queryData
                },
                success: function (res) {
                    if (res && res.success && res.data && res.data.html) {
                        $grid.append(res.data.html);
                        $btn.data('page', nextPage);

                        if (res.data.nonce) {
                            $btn.attr('data-nonce', res.data.nonce).data('nonce', res.data.nonce);
                        }

                        if (!res.data.has_more || nextPage >= maxPages) {
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

    });

})(jQuery);
