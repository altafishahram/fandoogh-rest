/**
 * TastyMenu Pro - Standalone PWA Dashboard JavaScript (Fondogh Online Menu)
 * Enhanced with: Modern Glassmorphism, Theme Switcher (Light & Dark),
 * Dynamic Category Filter Chips, Real-time KPI Stats, Instant Search & Glass Toasts
 */

(function () {
        'use strict';

    // Helper: Escape HTML to prevent DOM-based XSS
    function escapeHtml(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Helper: format prices with thousands separators and strip decimal zeros
    function formatThousand(val) {
        if (val === null || val === undefined || val === '') return '';
        var str = String(val).split('.')[0].replace(/[^0-9]/g, '');
        if (!str) return '';
        var n = parseInt(str, 10);
        return isNaN(n) ? '' : n.toLocaleString('en-US');
    }

    // 0. Theme Manager (Works on both Login & Dashboard Screens)
    function initTheme() {
        var themeToggleBtn = document.getElementById('rmm-theme-toggle-btn') || document.getElementById('rmm-login-theme-btn');
        var savedTheme = localStorage.getItem('rmm_portal_theme');
        if (!savedTheme) {
            savedTheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
        }
        applyTheme(savedTheme);

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', function () {
                var current = document.documentElement.getAttribute('data-theme') || 'light';
                var next = (current === 'dark') ? 'light' : 'dark';
                applyTheme(next);
                localStorage.setItem('rmm_portal_theme', next);
                showToast('تم به حالت ' + (next === 'dark' ? 'تیره (Dark)' : 'روشن (Light)') + ' تغییر یافت.', 'info');
            });
        }
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        var metaTheme = document.querySelector('meta[name="theme-color"]');
        if (metaTheme) {
            metaTheme.setAttribute('content', theme === 'dark' ? '#090d16' : '#dc2626');
        }

        var sunIcons = document.querySelectorAll('.theme-icon.sun');
        var moonIcons = document.querySelectorAll('.theme-icon.moon');
        if (theme === 'dark') {
            sunIcons.forEach(function (el) { el.style.display = 'none'; });
            moonIcons.forEach(function (el) { el.style.display = 'inline-block'; });
        } else {
            sunIcons.forEach(function (el) { el.style.display = 'inline-block'; });
            moonIcons.forEach(function (el) { el.style.display = 'none'; });
        }
    }

    initTheme();

    if (!window.rmmPortalConfig) return;

    var config = window.rmmPortalConfig;
    var state = {
        branchId: config.active_branch_id || 0,
        branches: config.branches || [],
        audioEnabled: config.enable_sound,
        lastKnownOrderIds: [],
        products: [],
        categories: [],
        activeCategory: 'all',
        searchQuery: '',
        orders: [],
        activeTab: 'orders'
    };

    // Ignore stale responses when the operator switches branches quickly.
    var branchRequestVersions = {
        orders: 0,
        products: 0,
        stats: 0,
        details: 0
    };
    var ordersRequestsInFlight = {};

    // DOM Elements
    var clockEl = document.getElementById('rmm-live-clock');
    var branchSelect = document.getElementById('rmm-portal-branch-select');
    var audioBtn = document.getElementById('rmm-audio-toggle-btn');
    var quickSettingsBtn = document.getElementById('rmm-quick-settings-btn');
    var tabBtns = document.querySelectorAll('.rmm-tab-btn');
    var tabContents = document.querySelectorAll('.rmm-tab-content');

    // Ensure branchId is resolved immediately
    if (!state.branchId && branchSelect && branchSelect.value) {
        state.branchId = parseInt(branchSelect.value, 10);
    }
    if (!state.branchId && state.branches && state.branches.length) {
        state.branchId = state.branches[0].id;
    }

    if (quickSettingsBtn) {
        quickSettingsBtn.addEventListener('click', function () {
            var settingsBtn = document.querySelector('.rmm-tab-btn[data-tab="settings"]');
            if (settingsBtn) {
                settingsBtn.click();
            }
        });
    }
    var ordersGrid = document.getElementById('rmm-orders-grid');
    var pendingBadge = document.getElementById('rmm-pending-badge');
    var productsBody = document.getElementById('rmm-products-body');
    var searchInput = document.getElementById('rmm-menu-search-input');
    var clearSearchBtn = document.getElementById('rmm-clear-search-btn');
    var categoryChipsWrap = document.getElementById('rmm-category-chips');
    var printFrame = document.getElementById('rmm-receipt-print-frame');
    var toggleBranchBtn = document.getElementById('rmm-toggle-branch-btn');
    var toastContainer = document.getElementById('rmm-toast-container');

    // Stats Elements
    var statTotalFoods = document.getElementById('rmm-stat-total-foods');
    var statInstockFoods = document.getElementById('rmm-stat-instock-foods');
    var statOutofstockFoods = document.getElementById('rmm-stat-outofstock-foods');
    var allCountBadge = document.getElementById('rmm-all-count');

    // 1. Toast Notification Helper
    function showToast(message, type) {
        type = type || 'success';
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'rmm-toast-container';
            toastContainer.className = 'rmm-toast-container';
            document.body.appendChild(toastContainer);
        }

        var icons = {
            success: '✅',
            error: '❌',
            info: '✨'
        };

        var toast = document.createElement('div');
        toast.className = 'rmm-toast ' + type;
        toast.innerHTML = '<span>' + (icons[type] || '🔔') + '</span><span>' + escapeHtml(message) + '</span>';

        toastContainer.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('fade-out');
            setTimeout(function () {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }, 3200);
    }
    window.rmmShowToast = showToast;

    // 2. Real-time Clock
    function updateClock() {
        if (!clockEl) return;
        var now = new Date();
        clockEl.textContent = now.toLocaleTimeString('fa-IR');
    }
    setInterval(updateClock, 1000);
    updateClock();

    // 3. Audio Chime (Web Audio API)
    var audioCtx = null;
    function playChime() {
        if (!state.audioEnabled) return;
        try {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (audioCtx.state === 'suspended') {
                audioCtx.resume();
            }

            var osc1 = audioCtx.createOscillator();
            var osc2 = audioCtx.createOscillator();
            var gain = audioCtx.createGain();

            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, audioCtx.currentTime); // D5
            osc1.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.3); // A5

            osc2.type = 'triangle';
            osc2.frequency.setValueAtTime(440, audioCtx.currentTime);

            gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.8);

            osc1.connect(gain);
            osc2.connect(gain);
            gain.connect(audioCtx.destination);

            osc1.start();
            osc2.start();
            osc1.stop(audioCtx.currentTime + 0.8);
            osc2.stop(audioCtx.currentTime + 0.8);
        } catch (e) {
            console.warn('Audio chime notice:', e);
        }
    }

    if (audioBtn) {
        audioBtn.addEventListener('click', function () {
            state.audioEnabled = !state.audioEnabled;
            audioBtn.classList.toggle('active', state.audioEnabled);
            if (state.audioEnabled) {
                playChime();
                showToast('صدای زنگ سفارش فعال شد.', 'info');
            } else {
                showToast('صدای زنگ سفارش غیرفعال شد.', 'info');
            }
        });
    }

    // 4. Tab Navigation
    tabBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.getAttribute('data-tab');
            state.activeTab = tab;

            tabBtns.forEach(function (b) { b.classList.remove('active'); });
            tabContents.forEach(function (c) { c.classList.remove('active'); });

            btn.classList.add('active');
            var target = document.getElementById('tab-' + tab);
            if (target) target.classList.add('active');

            if (tab === 'menu') {
                fetchProducts();
            } else if (tab === 'orders') {
                fetchOrders();
            }
        });
    });

    // 5. Branch Selection
    if (branchSelect) {
        branchSelect.addEventListener('change', function () {
            state.branchId = parseInt(this.value, 10) || 0;
            // Do not compare the new branch's orders with the previous branch's IDs.
            state.lastKnownOrderIds = [];
            state.orders = [];
            showToast('در حال جابجایی به شعبه جدید...', 'info');
            fetchProducts();
            fetchOrders();
            fetchStats();
            fetchCurrentBranchDetails();
        });
    }

    // Track connection changes once, instead of showing an error toast for every failed poll.
    function setConnectionState(isOnline) {
        if (state.connectionOnline === isOnline) return;
        state.connectionOnline = isOnline;
        showToast(
            isOnline ? 'ارتباط با سرور برقرار شد.' : 'ارتباط با سرور قطع شده است؛ تلاش مجدد پس از اتصال.',
            isOnline ? 'success' : 'error'
        );
    }

    state.connectionOnline = true;

    // 6. Generic REST API Client
    function apiRequest(endpoint, method, data) {
        method = method || 'GET';
        var url = config.rest_url + endpoint;
        var headers = {
            'X-WP-Nonce': config.nonce
        };

        var options = {
            method: method,
            headers: headers
        };

        if (method === 'GET' && data) {
            var params = new URLSearchParams(data).toString();
            url += '?' + params;
        } else if (data) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }

        return fetch(url, options)
            .then(function (res) {
                setConnectionState(true);
                return res.json().catch(function (err) {
                    console.error('Invalid API response:', endpoint, err);
                    showToast('پاسخ نامعتبر از سرور دریافت شد.', 'error');
                    return null;
                });
            })
            .catch(function (err) {
                console.error('API Error:', endpoint, err);
                setConnectionState(false);
                return null;
            });
    }

    // 7. Kitchen Orders Display System (KDS)
    function fetchOrders() {
        var requestedBranchId = state.branchId;
        if (!requestedBranchId || document.hidden || navigator.onLine === false) return;

        var requestKey = String(requestedBranchId);
        if (ordersRequestsInFlight[requestKey]) return;
        ordersRequestsInFlight[requestKey] = true;

        var requestVersion = ++branchRequestVersions.orders;

        apiRequest('live-orders', 'GET', { branch_id: requestedBranchId })
            .then(function (res) {
                if (requestVersion !== branchRequestVersions.orders || requestedBranchId !== state.branchId) return;
                if (res && res.success && res.orders) {
                    state.orders = res.orders;
                    processNewOrdersNotification(res.orders);
                    renderOrders(res.orders);
                }
            })
            .finally(function () {
                delete ordersRequestsInFlight[requestKey];
            });
    }

    function processNewOrdersNotification(orders) {
        var processing = orders.filter(function (o) { return o.status === 'processing'; });
        var count = processing.length;

        if (pendingBadge) {
            if (count > 0) {
                pendingBadge.textContent = count;
                pendingBadge.style.display = 'inline-block';
            } else {
                pendingBadge.style.display = 'none';
            }
        }

        var currentIds = processing.map(function (o) { return o.id; });
        var hasNew = currentIds.some(function (id) {
            return state.lastKnownOrderIds.indexOf(id) === -1;
        });

        if (hasNew && state.lastKnownOrderIds.length > 0) {
            playChime();
            showToast('🛎️ سفارش جدیدی در آشپزخانه ثبت شد!', 'success');
        }

        state.lastKnownOrderIds = currentIds;
    }

    function renderOrders(orders) {
        if (!ordersGrid) return;

        if (orders.length === 0) {
            ordersGrid.innerHTML = '<div class="rmm-loading-state" style="grid-column: 1/-1; text-align:center; padding:40px; color:var(--rmm-text-muted); font-size:15px;">هیچ سفارشی در این وضعیت موجود نیست.</div>';
            return;
        }

        var html = '';
        orders.forEach(function (order) {
            var typeBadge = (order.order_type === 'dine_in')
                ? '<span class="rmm-order-type-badge rmm-type-dine_in">🍽️ میز ' + escapeHtml(order.table_number || '-') + '</span>'
                : '<span class="rmm-order-type-badge rmm-type-delivery">🛵 بیرون‌بر</span>';

            var itemsHtml = '';
            order.items.forEach(function (it) {
                var metaHtml = '';
                if (it.meta && it.meta.length) {
                    var safeMeta = it.meta.map(function(m) { return escapeHtml(m); });
                    metaHtml = '<div class="rmm-item-mods">' + safeMeta.join(' | ') + '</div>';
                }
                itemsHtml += '<li class="rmm-card-item"><div><strong>' + escapeHtml(it.quantity) + 'x</strong> ' + escapeHtml(it.name) + metaHtml + '</div><span>' + Number(it.total).toLocaleString('fa-IR') + '</span></li>';
            });

            var nextStatusBtn = '';
            if (order.status === 'processing') {
                nextStatusBtn = '<button class="rmm-btn-status-toggle" style="background:#10b981; color:#fff;" onclick="window.rmmUpdateOrderStatus(' + order.id + ', \'completed\')">✅ تحویل شد</button>';
            } else {
                nextStatusBtn = '<span style="font-size:12px; color:var(--rmm-success); font-weight:bold;">✔ انجام شده</span>';
            }

            html += '<div class="rmm-order-card ' + escapeHtml(order.status) + '">' +
                '<div class="rmm-card-header">' +
                '<div><div class="rmm-order-id">سفارش #' + escapeHtml(order.id) + '</div><div style="font-size:12px; color:var(--rmm-text-muted); margin-top:2px;">' + escapeHtml(order.created_at) + ' | ' + escapeHtml(order.customer_name) + '</div></div>' +
                '<div>' + typeBadge + '</div>' +
                '</div>' +
                '<ul class="rmm-card-items">' + itemsHtml + '</ul>' +
                (order.notes ? '<div style="font-size:12px; background:var(--rmm-warning-bg); color:var(--rmm-warning); padding:6px 10px; border-radius:8px; margin-bottom:10px; border:1px solid var(--rmm-warning);">📝 ' + escapeHtml(order.notes) + '</div>' : '') +
                '<div class="rmm-card-footer">' +
                '<div style="font-weight:800; font-size:14px; color:var(--rmm-text);">مجموع: ' + Number(order.total).toLocaleString('fa-IR') + ' ' + escapeHtml(order.currency) + '</div>' +
                '<div style="display:flex; gap:8px;">' +
                '<button class="rmm-btn-print-receipt" onclick="window.rmmPrintReceipt(' + order.id + ')">🖨️ فیش</button>' +
                nextStatusBtn +
                '</div>' +
                '</div>' +
                '</div>';
        });

        ordersGrid.innerHTML = html;
    }

    // Global order status handler
    window.rmmUpdateOrderStatus = function (orderId, newStatus) {
        apiRequest('update-order-status', 'POST', { order_id: orderId, status: newStatus })
            .then(function (res) {
                if (res && res.success) {
                    showToast('وضعیت سفارش #' + orderId + ' به‌روز شد.', 'success');
                    fetchOrders();
                }
            });
    };

    // Filter Buttons for Orders
    var orderFilters = document.querySelectorAll('.rmm-order-filter');
    orderFilters.forEach(function (btn) {
        btn.addEventListener('click', function () {
            orderFilters.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var f = btn.getAttribute('data-filter');
            if (f === 'all') {
                renderOrders(state.orders);
            } else {
                renderOrders(state.orders.filter(function (o) { return o.status === f; }));
            }
        });
    });

    // 8. Thermal Receipt 80mm Print
    window.rmmPrintReceipt = function (orderId) {
        apiRequest('order-receipt', 'GET', { order_id: orderId })
            .then(function (data) {
                if (!data || !printFrame) return;

                var itemsRows = '';
                data.items.forEach(function (it) {
                    var safeMods = (it.modifiers && it.modifiers.length)
                        ? '<div class="item-mods">' + it.modifiers.map(escapeHtml).join(', ') + '</div>'
                        : '';
                    itemsRows += '<tr>' +
                        '<td>' + escapeHtml(it.name) + safeMods + '</td>' +
                        '<td style="text-align:center;">' + escapeHtml(it.qty) + '</td>' +
                        '<td style="text-align:left;">' + Number(it.total).toLocaleString('fa-IR') + '</td>' +
                        '</tr>';
                });

                var tableBanner = data.table_number ? '<div class="table-banner">میز شماره ' + escapeHtml(data.table_number) + '</div>' : '';

                var receiptHtml = '<div class="receipt-header">' +
                    '<h2 class="restaurant-title">' + escapeHtml(data.restaurant) + '</h2>' +
                    '<div class="branch-info">' + escapeHtml(data.branch_name || '') + (data.branch_phone ? ' - ' + escapeHtml(data.branch_phone) : '') + '</div>' +
                    '</div>' +
                    tableBanner +
                    '<div class="meta-line"><span>شماره فاکتور: #' + escapeHtml(data.order_id) + '</span><span>' + escapeHtml(data.date) + '</span></div>' +
                    '<div class="meta-line"><span>مشتری: ' + escapeHtml(data.customer_name) + '</span><span>' + escapeHtml(data.phone || '') + '</span></div>' +
                    '<table class="items-table">' +
                    '<thead><tr><th>غذا</th><th style="text-align:center;">تعداد</th><th style="text-align:left;">قیمت</th></tr></thead>' +
                    '<tbody>' + itemsRows + '</tbody>' +
                    '</table>' +
                    '<div class="receipt-total">' +
                    '<div class="total-row"><span>مبلغ کل:</span><span>' + Number(data.total).toLocaleString('fa-IR') + ' ' + escapeHtml(data.currency) + '</span></div>' +
                    '</div>' +
                    (data.notes ? '<div class="notes-box">یادداشت: ' + escapeHtml(data.notes) + '</div>' : '') +
                    '<div class="receipt-footer">با تشکر از انتخاب شما | بازگشت مجدد شما افتخار ماست</div>';

                var doc = printFrame.contentDocument || printFrame.contentWindow.document;
                doc.open();
                doc.write('<html><head><meta charset="UTF-8"><style>' +
                    '@page { size: 80mm auto; margin: 0; }' +
                    'body { width: 78mm; margin: 0 auto; padding: 6px; font-family: Tahoma, Arial, sans-serif; font-size: 11px; direction: rtl; }' +
                    '.receipt-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 6px; }' +
                    '.restaurant-title { font-size: 15px; font-weight: bold; margin: 0 0 2px; }' +
                    '.branch-info { font-size: 10px; }' +
                    '.table-banner { background: #000; color: #fff; font-size: 14px; font-weight: bold; padding: 4px; margin: 6px 0; text-align: center; }' +
                    '.meta-line { display: flex; justify-content: space-between; font-size: 10px; margin-bottom: 3px; }' +
                    '.items-table { width: 100%; border-collapse: collapse; margin: 6px 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }' +
                    '.items-table th, .items-table td { padding: 3px 0; font-size: 10px; }' +
                    '.item-mods { font-size: 9px; color: #444; padding-right: 6px; }' +
                    '.total-row { display: flex; justify-content: space-between; font-size: 13px; font-weight: bold; margin-top: 4px; }' +
                    '.notes-box { border: 1px solid #000; padding: 3px; margin-top: 6px; font-size: 10px; }' +
                    '.receipt-footer { text-align: center; font-size: 9px; margin-top: 8px; border-top: 1px dashed #000; padding-top: 4px; }' +
                    '</style></head><body>' + receiptHtml + '</body></html>');
                doc.close();

                setTimeout(function () {
                    printFrame.contentWindow.focus();
                    printFrame.contentWindow.print();
                }, 300);
            });
    };

    // 9. Products & Inventory Management
    function fetchProducts() {
        var requestedBranchId = state.branchId;
        var requestVersion = ++branchRequestVersions.products;

        apiRequest('products', 'GET', { branch_id: requestedBranchId })
            .then(function (res) {
                if (requestVersion !== branchRequestVersions.products || requestedBranchId !== state.branchId) return;
                if (res && res.success && res.products) {
                    state.products = res.products;
                    updateMenuStats();
                    renderCategoryChips();
                    filterAndRenderProducts();
                } else {
                    if (productsBody) {
                        productsBody.innerHTML = '<tr><td colspan="7" class="rmm-center" style="padding:30px; text-align:center; color:var(--rmm-text-muted);">هیچ محصولی در ووکامرس یافت نشد.</td></tr>';
                    }
                }
            });
    }

    function updateMenuStats() {
        var total = state.products.length;
        var inStock = state.products.filter(function (p) { return p.is_available; }).length;
        var outOfStock = total - inStock;

        if (statTotalFoods) statTotalFoods.textContent = total.toLocaleString('fa-IR');
        if (statInstockFoods) statInstockFoods.textContent = inStock.toLocaleString('fa-IR');
        if (statOutofstockFoods) statOutofstockFoods.textContent = outOfStock.toLocaleString('fa-IR');
        if (allCountBadge) allCountBadge.textContent = total;
    }

    function renderCategoryChips() {
        if (!categoryChipsWrap) return;

        var html = '<button class="rmm-cat-chip ' + (state.activeCategory === 'all' ? 'active' : '') + '" data-cat="all">' +
            '<span>🍽️</span>' +
            '<span>همه دسته‌ها</span>' +
            '<span class="rmm-chip-count">' + state.products.length + '</span>' +
            '</button>';

        state.categories.forEach(function (cat) {
            var count = state.products.filter(function (p) {
                return (p.category_id && p.category_id == cat.id) ||
                       (p.categories && p.categories.indexOf(cat.name) !== -1);
            }).length;

            var isActive = (state.activeCategory == cat.id) ? 'active' : '';
            html += '<button class="rmm-cat-chip ' + isActive + '" data-cat="' + escapeHtml(cat.id) + '">' +
                '<span>🍲</span>' +
                '<span>' + escapeHtml(cat.name) + '</span>' +
                '<span class="rmm-chip-count">' + escapeHtml(count) + '</span>' +
                '</button>';
        });

        categoryChipsWrap.innerHTML = html;

        // Attach chip click listeners
        categoryChipsWrap.querySelectorAll('.rmm-cat-chip').forEach(function (chip) {
            chip.addEventListener('click', function () {
                categoryChipsWrap.querySelectorAll('.rmm-cat-chip').forEach(function (c) { c.classList.remove('active'); });
                chip.classList.add('active');
                state.activeCategory = chip.getAttribute('data-cat');
                filterAndRenderProducts();
            });
        });
    }

    function filterAndRenderProducts() {
        var filtered = state.products.filter(function (p) {
            // Category check
            var matchesCategory = true;
            if (state.activeCategory !== 'all') {
                var catId = parseInt(state.activeCategory, 10);
                var catName = '';
                var foundCat = state.categories.find(function (c) { return c.id == catId; });
                if (foundCat) catName = foundCat.name;

                matchesCategory = (p.category_id == catId) || (catName && p.categories && p.categories.indexOf(catName) !== -1);
            }

            // Search query check
            var matchesSearch = true;
            if (state.searchQuery) {
                var q = state.searchQuery.toLowerCase();
                var nameMatch = p.name && p.name.toLowerCase().indexOf(q) !== -1;
                var nameEnMatch = p.name_en && p.name_en.toLowerCase().indexOf(q) !== -1;
                var ingMatch = p.ingredients && p.ingredients.toLowerCase().indexOf(q) !== -1;
                matchesSearch = nameMatch || nameEnMatch || ingMatch;
            }

            return matchesCategory && matchesSearch;
        });

        renderProducts(filtered);
    }

    function renderProducts(products) {
        if (!productsBody) return;
        if (products.length === 0) {
            productsBody.innerHTML = '<tr><td colspan="7" style="text-align:center; padding:40px; color:var(--rmm-text-muted); font-size:14px;">هیچ غذایی با این مشخصات یافت نشد.</td></tr>';
            return;
        }

        var badgeMap = {
            'chef': '⭐ پیشنهاد سرآشپز',
            'spicy': '🌶️ تند',
            'vegan': '🌱 گیاهی',
            'popular': '🔥 پرفروش',
            'new': '✨ جدید',
            'custom': '🎨 اختصاصی'
        };

        var html = '';
        products.forEach(function (p) {
            var checked = p.is_available ? 'checked' : '';
            var cats = (p.categories && p.categories.length) ? p.categories.join('، ') : '-';
            var badgeLabel = (p.badge_text) ? p.badge_text : (badgeMap[p.badge] || (p.badge !== 'none' && p.badge !== 'custom' ? p.badge : ''));
            var badgeIcon = (p.badge_custom_icon) ? p.badge_custom_icon : '';
            var badgeImg = (p.badge_image) ? '<img src="' + escapeHtml(p.badge_image) + '" style="width:14px;height:14px;object-fit:contain;vertical-align:middle;margin-left:3px;border-radius:3px;">' : '';
            var badgeHtml = (badgeLabel || badgeImg || (p.badge && p.badge !== 'none')) ? '<span class="rmm-card-badge-pill">' + badgeImg + (badgeIcon ? escapeHtml(badgeIcon) + ' ' : '') + escapeHtml(badgeLabel) + '</span>' : '';

            html += '<tr class="rmm-food-row-card">' +
                '<td class="rmm-cell-thumb"><img src="' + escapeHtml(p.image) + '" class="rmm-food-thumb" alt="" loading="lazy"></td>' +
                '<td class="rmm-cell-info">' +
                '  <div class="rmm-row-title-wrap">' +
                '    <div class="rmm-row-title">' + escapeHtml(p.name) + badgeHtml + '</div>' +
                (p.name_en ? '    <div class="rmm-row-title-en">' + escapeHtml(p.name_en) + '</div>' : '') +
                '  </div>' +
                '</td>' +
                '<td class="rmm-cell-cat"><span class="rmm-cat-badge">' + escapeHtml(cats) + '</span></td>' +
                '<td class="rmm-cell-reg-price">' +
                '  <div class="rmm-price-control-group">' +
                '    <span class="rmm-mobile-cell-label">قیمت عادی:</span>' +
                '    <div class="rmm-input-unit-wrap">' +
                '      <input type="text" inputmode="numeric" class="rmm-price-input" data-id="' + p.id + '" data-field="reg" value="' + formatThousand(p.regular_price || 0) + '">' +
                '      <span class="rmm-cell-unit">تومان</span>' +
                '    </div>' +
                '  </div>' +
                '</td>' +
                '<td class="rmm-cell-sale-price">' +
                '  <div class="rmm-price-control-group">' +
                '    <span class="rmm-mobile-cell-label">قیمت ویژه:</span>' +
                '    <div class="rmm-input-unit-wrap">' +
                '      <input type="text" inputmode="numeric" class="rmm-price-input" data-id="' + p.id + '" data-field="sale" value="' + (p.sale_price ? formatThousand(p.sale_price) : '') + '" placeholder="-">' +
                '      <span class="rmm-cell-unit">تومان</span>' +
                '    </div>' +
                '  </div>' +
                '</td>' +
                '<td class="rmm-cell-stock">' +
                '  <div class="rmm-stock-control-group">' +
                '    <span class="rmm-mobile-cell-label">موجودی شعبه:</span>' +
                '    <div class="rmm-stock-toggle-box">' +
                '      <label class="rmm-switch" title="' + (p.is_available ? 'موجود در این شعبه' : 'ناموجود') + '">' +
                '        <input type="checkbox" class="rmm-stock-toggle" data-id="' + p.id + '" ' + checked + '>' +
                '        <span class="rmm-slider"></span>' +
                '      </label>' +
                '      <span class="rmm-stock-status-pill ' + (p.is_available ? 'is-in' : 'is-out') + '">' + (p.is_available ? 'موجود' : 'ناموجود') + '</span>' +
                '    </div>' +
                '  </div>' +
                '</td>' +
                '<td class="rmm-cell-actions">' +
                '  <div class="rmm-action-btn-group">' +
                '    <button type="button" class="rmm-table-btn btn-save" title="ذخیره قیمت" onclick="window.rmmSavePrice(' + p.id + ')"><svg width="15" height="15" style="vertical-align:middle; display:inline-block;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M17 21.25H7C3.35 21.25 1.25 19.15 1.25 15.5V8.5C1.25 4.85 3.35 2.75 7 2.75H14C14.41 2.75 14.75 3.09 14.75 3.5C14.75 3.91 14.41 4.25 14 4.25H7C4.14 4.25 2.75 5.64 2.75 8.5V15.5C2.75 18.36 4.14 19.75 7 19.75H17C19.86 19.75 21.25 18.36 21.25 15.5V10.5C21.25 10.09 21.59 9.75 22 9.75C22.41 9.75 22.75 10.09 22.75 10.5V15.5C22.75 19.15 20.65 21.25 17 21.25Z"/> <path d="M11.9998 12.8707C11.1598 12.8707 10.3098 12.6108 9.65978 12.0808L6.52978 9.58076C6.20978 9.32076 6.14978 8.85075 6.40978 8.53075C6.66978 8.21075 7.13977 8.15076 7.45977 8.41076L10.5898 10.9108C11.3498 11.5208 12.6398 11.5208 13.3998 10.9108L14.5798 9.97076C14.8998 9.71076 15.3798 9.76075 15.6298 10.0908C15.8898 10.4108 15.8398 10.8908 15.5098 11.1408L14.3298 12.0808C13.6898 12.6108 12.8398 12.8707 11.9998 12.8707Z"/> <path d="M19.5 8.75C17.71 8.75 16.25 7.29 16.25 5.5C16.25 3.71 17.71 2.25 19.5 2.25C21.29 2.25 22.75 3.71 22.75 5.5C22.75 7.29 21.29 8.75 19.5 8.75ZM19.5 3.75C18.54 3.75 17.75 4.54 17.75 5.5C17.75 6.46 18.54 7.25 19.5 7.25C20.46 7.25 21.25 6.46 21.25 5.5C21.25 4.54 20.46 3.75 19.5 3.75Z"/> </svg> <span>ثبت قیمت</span></button>' +
                '    <button type="button" class="rmm-table-btn btn-edit" title="ویرایش کامل غذا" onclick="window.rmmEditFood(' + p.id + ')"><svg width="15" height="15" style="vertical-align:middle; display:inline-block;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M15 22.75H9C3.57 22.75 1.25 20.43 1.25 15V9C1.25 3.57 3.57 1.25 9 1.25H11C11.41 1.25 11.75 1.59 11.75 2C11.75 2.41 11.41 2.75 11 2.75H9C4.39 2.75 2.75 4.39 2.75 9V15C2.75 19.61 4.39 21.25 9 21.25H15C19.61 21.25 21.25 19.61 21.25 15V13C21.25 12.59 21.59 12.25 22 12.25C22.41 12.25 22.75 12.59 22.75 13V15C22.75 20.43 20.43 22.75 15 22.75Z"/> <path d="M8.50008 17.6905C7.89008 17.6905 7.33008 17.4705 6.92008 17.0705C6.43008 16.5805 6.22008 15.8705 6.33008 15.1205L6.76008 12.1105C6.84008 11.5305 7.22008 10.7805 7.63008 10.3705L15.5101 2.49055C17.5001 0.500547 19.5201 0.500547 21.5101 2.49055C22.6001 3.58055 23.0901 4.69055 22.9901 5.80055C22.9001 6.70055 22.4201 7.58055 21.5101 8.48055L13.6301 16.3605C13.2201 16.7705 12.4701 17.1505 11.8901 17.2305L8.88008 17.6605C8.75008 17.6905 8.62008 17.6905 8.50008 17.6905ZM16.5701 3.55055L8.69008 11.4305C8.50008 11.6205 8.28008 12.0605 8.24008 12.3205L7.81008 15.3305C7.77008 15.6205 7.83008 15.8605 7.98008 16.0105C8.13008 16.1605 8.37008 16.2205 8.66008 16.1805L11.6701 15.7505C11.9301 15.7105 12.3801 15.4905 12.5601 15.3005L20.4401 7.42055C21.0901 6.77055 21.4301 6.19055 21.4801 5.65055C21.5401 5.00055 21.2001 4.31055 20.4401 3.54055C18.8401 1.94055 17.7401 2.39055 16.5701 3.55055Z"/> <path d="M19.8501 9.83027C19.7801 9.83027 19.7101 9.82027 19.6501 9.80027C17.0201 9.06027 14.9301 6.97027 14.1901 4.34027C14.0801 3.94027 14.3101 3.53027 14.7101 3.41027C15.1101 3.30027 15.5201 3.53027 15.6301 3.93027C16.2301 6.06027 17.9201 7.75027 20.0501 8.35027C20.4501 8.46027 20.6801 8.88027 20.5701 9.28027C20.4801 9.62027 20.1801 9.83027 19.8501 9.83027Z"/> </svg> <span>ویرایش</span></button>' +
                '    <button type="button" class="rmm-table-btn btn-delete" title="حذف" onclick="window.rmmDeleteFood(' + p.id + ')"><svg width="15" height="15" style="vertical-align:middle; display:inline-block;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M13.3901 18.1091C13.2001 18.1091 13.0101 18.0391 12.8601 17.8891L10.1001 15.1291C9.81007 14.8391 9.81007 14.3591 10.1001 14.0691C10.3901 13.7791 10.8701 13.7791 11.1601 14.0691L13.9201 16.8291C14.2101 17.1191 14.2101 17.5991 13.9201 17.8891C13.7801 18.0391 13.5901 18.1091 13.3901 18.1091Z"/> <path d="M10.61 18.1403C10.42 18.1403 10.23 18.0703 10.08 17.9203C9.79005 17.6303 9.79005 17.1503 10.08 16.8603L12.84 14.1003C13.13 13.8103 13.61 13.8103 13.9 14.1003C14.19 14.3903 14.19 14.8703 13.9 15.1603L11.14 17.9203C10.99 18.0703 10.8 18.1403 10.61 18.1403Z"/> <path d="M5.18988 6.37945C4.99988 6.37945 4.79988 6.29945 4.65988 6.15945C4.36988 5.86945 4.36988 5.38945 4.65988 5.09945L8.28988 1.46945C8.57988 1.17945 9.05988 1.17945 9.34988 1.46945C9.63988 1.75945 9.63988 2.23945 9.34988 2.52945L5.71988 6.15945C5.56988 6.29945 5.37988 6.37945 5.18988 6.37945Z"/> <path d="M18.8101 6.37945C18.6201 6.37945 18.4301 6.30945 18.2801 6.15945L14.6501 2.52945C14.3601 2.23945 14.3601 1.75945 14.6501 1.46945C14.9401 1.17945 15.4201 1.17945 15.7101 1.46945L19.3401 5.09945C19.6301 5.38945 19.6301 5.86945 19.3401 6.15945C19.2001 6.29945 19.0001 6.37945 18.8101 6.37945Z"/> <path d="M20.21 10.5996C20.14 10.5996 20.07 10.5996 20 10.5996H19.77H4C3.3 10.6096 2.5 10.6096 1.92 10.0296C1.46 9.57961 1.25 8.87961 1.25 7.84961C1.25 5.09961 3.26 5.09961 4.22 5.09961H19.78C20.74 5.09961 22.75 5.09961 22.75 7.84961C22.75 8.88961 22.54 9.57961 22.08 10.0296C21.56 10.5496 20.86 10.5996 20.21 10.5996ZM4.22 9.09961H20.01C20.46 9.10961 20.88 9.10961 21.02 8.96961C21.09 8.89961 21.24 8.65961 21.24 7.84961C21.24 6.71961 20.96 6.59961 19.77 6.59961H4.22C3.03 6.59961 2.75 6.71961 2.75 7.84961C2.75 8.65961 2.91 8.89961 2.97 8.96961C3.11 9.09961 3.54 9.09961 3.98 9.09961H4.22Z"/> <path d="M14.89 22.7507H8.86C5.28 22.7507 4.48 20.6207 4.17 18.7707L2.76 10.1207C2.69 9.71073 2.97 9.33073 3.38 9.26073C3.79 9.19073 4.17 9.47073 4.24 9.88073L5.65 18.5207C5.94 20.2907 6.54 21.2507 8.86 21.2507H14.89C17.46 21.2507 17.75 20.3507 18.08 18.6107L19.76 9.86073C19.84 9.45073 20.23 9.18073 20.64 9.27073C21.05 9.35073 21.31 9.74073 21.23 10.1507L19.55 18.9007C19.16 20.9307 18.51 22.7507 14.89 22.7507Z"/> </svg></button>' +
                '  </div>' +
                '</td>' +
                '</tr>';
        });

        productsBody.innerHTML = html;

        // Attach event listeners for stock toggles
        document.querySelectorAll('.rmm-stock-toggle').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var pId = this.getAttribute('data-id');
                var status = this.checked ? 'instock' : 'outofstock';
                
                // Optimistic UI update
                var food = state.products.find(function (f) { return f.id == pId; });
                if (food) food.is_available = (status === 'instock');
                updateMenuStats();

                apiRequest('toggle-stock', 'POST', {
                    product_id: pId,
                    branch_id: state.branchId,
                    status: status
                }).then(function (res) {
                    if (res && res.success) {
                        showToast('وضعیت موجودی غذا به‌روزرسانی شد.', 'success');
                    } else {
                        showToast('خطا در به‌روزرسانی موجودی', 'error');
                        fetchProducts();
                    }
                });
            });
        });
    }

    // Save Price Inline
    window.rmmSavePrice = function (productId) {
        var regInput = document.querySelector('.rmm-price-input[data-id="' + productId + '"][data-field="reg"]');
        var saleInput = document.querySelector('.rmm-price-input[data-id="' + productId + '"][data-field="sale"]');

        var regVal = regInput ? regInput.value.replace(/,/g, '').trim() : '0';
        var saleVal = (saleInput && saleInput.value) ? saleInput.value.replace(/,/g, '').trim() : '';
        var regPrice = parseFloat(regVal) || 0;
        var salePrice = saleVal ? parseFloat(saleVal) : null;

        apiRequest('update-price', 'POST', {
            product_id: productId,
            branch_id: state.branchId,
            regular_price: regPrice,
            sale_price: salePrice
        }).then(function (res) {
            if (res && res.success) {
                showToast('قیمت غذا با موفقیت برای این شعبه ثبت شد.', 'success');
            } else {
                showToast('خطا در ثبت قیمت', 'error');
            }
        });
    };

    // Search input handler
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            state.searchQuery = this.value.trim();
            if (clearSearchBtn) {
                clearSearchBtn.style.display = state.searchQuery ? 'block' : 'none';
            }
            filterAndRenderProducts();
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function () {
            searchInput.value = '';
            state.searchQuery = '';
            clearSearchBtn.style.display = 'none';
            filterAndRenderProducts();
            searchInput.focus();
        });
    }

    // 10. QR Code Generator
    var genQrBtn = document.getElementById('rmm-generate-qr-btn');
    var printQrBtn = document.getElementById('rmm-print-qr-btn');
    var qrTableNum = document.getElementById('rmm-qr-table-num');
    var qrPreviewArea = document.getElementById('rmm-qr-preview-area');

    function renderQRPreview(tableNo) {
        if (!qrPreviewArea) return;
        var targetUrl = window.location.origin + '/?branch=' + state.branchId + '&table=' + tableNo;
        var qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(targetUrl);

        qrPreviewArea.innerHTML = '<div style="border:2px dashed var(--rmm-secondary); border-radius:16px; padding:24px; text-align:center; max-width:280px; background:var(--rmm-glass-bg); backdrop-filter:var(--rmm-glass-blur); box-shadow:var(--rmm-glass-shadow);">' +
            '<h3 style="margin-bottom:12px; font-weight:800; color:var(--rmm-text);">میز شماره ' + tableNo + '</h3>' +
            '<img src="' + qrImgUrl + '" style="width:200px; height:200px; display:block; margin:0 auto 12px; border-radius:8px;" alt="">' +
            '<p style="font-size:12px; color:var(--rmm-text-muted);">اسکن برای سفارش آنلاین مستقیم</p>' +
            '</div>';
    }

    if (genQrBtn && qrTableNum) {
        genQrBtn.addEventListener('click', function () {
            var t = parseInt(qrTableNum.value, 10) || 1;
            renderQRPreview(t);
            showToast('QR کد میز ' + t + ' تولید شد.', 'info');
        });
    }

    if (printQrBtn && qrTableNum) {
        printQrBtn.addEventListener('click', function () {
            var t = parseInt(qrTableNum.value, 10) || 1;
            var targetUrl = window.location.origin + '/?branch=' + state.branchId + '&table=' + t;
            var qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(targetUrl);

            var w = window.open('', '_blank');
            w.document.write('<html><head><title>میز ' + t + '</title><style>body { text-align:center; font-family:tahoma; padding:40px; } .card { border:2px dashed #000; padding:30px; display:inline-block; border-radius:12px; }</style></head><body>' +
                '<div class="card">' +
                '<h2>میز شماره ' + t + '</h2>' +
                '<img src="' + qrImgUrl + '" style="width:250px; height:250px; margin:15px 0;">' +
                '<h3>اسکن کنید و آنلاین سفارش دهید</h3>' +
                '</div>' +
                '</body></html>');
            w.document.close();
            setTimeout(function () { w.print(); }, 400);
        });
    }

    // 11. Fetch Stats & Branch Shift
    function fetchStats() {
        var requestedBranchId = state.branchId;
        var requestVersion = ++branchRequestVersions.stats;

        apiRequest('stats', 'GET', { branch_id: requestedBranchId })
            .then(function (res) {
                if (requestVersion !== branchRequestVersions.stats || requestedBranchId !== state.branchId) return;
                if (res && res.success && toggleBranchBtn) {
                    if (res.branch_is_open) {
                        toggleBranchBtn.className = 'rmm-btn-status open';
                        toggleBranchBtn.textContent = '🟢 شعبه باز است (کلیک برای بستن)';
                    } else {
                        toggleBranchBtn.className = 'rmm-btn-status closed';
                        toggleBranchBtn.textContent = '🔴 شعبه بسته است (کلیک برای باز کردن)';
                    }
                }
            });
    }

    if (toggleBranchBtn) {
        toggleBranchBtn.addEventListener('click', function () {
            var isOpen = toggleBranchBtn.classList.contains('open');
            var newStatus = isOpen ? '0' : '1';

            apiRequest('toggle-branch', 'POST', {
                branch_id: state.branchId,
                is_open: newStatus
            }).then(function (res) {
                if (res && res.success) {
                    if (res.is_open) {
                        toggleBranchBtn.className = 'rmm-btn-status open';
                        toggleBranchBtn.textContent = '🟢 شعبه باز است (کلیک برای بستن)';
                        showToast('شعبه برای ثبت سفارش آنلاین باز شد.', 'success');
                    } else {
                        toggleBranchBtn.className = 'rmm-btn-status closed';
                        toggleBranchBtn.textContent = '🔴 شعبه بسته است (کلیک برای باز کردن)';
                        showToast('شعبه بسته شد (سفارش جدید غیرفعال شد).', 'info');
                    }
                }
            });
        });
    }

    // 12. Food CRUD (Add / Edit / Delete Food)
    var foodModal = document.getElementById('rmm-food-edit-modal');
    var addFoodBtn = document.getElementById('rmm-add-food-btn');
    var closeFoodModalBtn = document.getElementById('rmm-close-food-modal-btn');
    var cancelFoodBtn = document.getElementById('rmm-cancel-food-btn');
    var foodForm = document.getElementById('rmm-food-form');
    var categorySelect = document.getElementById('rmm-food-form-category');

    function fetchCategories() {
        apiRequest('categories', 'GET').then(function (res) {
            if (res && res.success && res.categories) {
                state.categories = res.categories;

                if (categorySelect) {
                    var html = '<option value="0">-- بدون دسته‌بندی --</option>';
                    res.categories.forEach(function (cat) {
                        html += '<option value="' + escapeHtml(cat.id) + '">' + escapeHtml(cat.name) + '</option>';
                    });
                    categorySelect.innerHTML = html;
                }

                renderCategoryChips();
                if (catModal && catModal.style.display === 'flex') {
                    renderCategoryList();
                }
            }
        });
    }

    function openFoodModal(product) {
        if (!foodModal) return;
        fetchCategories();

        var titleEl = document.getElementById('rmm-food-modal-title');
        var idEl = document.getElementById('rmm-food-form-id');
        var nameEl = document.getElementById('rmm-food-form-name');
        var descEl = document.getElementById('rmm-food-form-desc');
        var priceEl = document.getElementById('rmm-food-form-price');
        var salePriceEl = document.getElementById('rmm-food-form-sale-price');
        var caloriesEl = document.getElementById('rmm-food-form-calories');
        var prepTimeEl = document.getElementById('rmm-food-form-prep-time');
        var ingredientsEl = document.getElementById('rmm-food-form-ingredients');
        var badgeEl = document.getElementById('rmm-food-form-badge');

        if (product) {
            titleEl.textContent = 'ویرایش غذا: ' + product.name;
            idEl.value = product.id;
            nameEl.value = product.name || '';
            if (descEl) descEl.value = product.description || '';
            priceEl.value = formatThousand(product.regular_price || 0);
            salePriceEl.value = formatThousand(product.sale_price || '');
            caloriesEl.value = product.calories || '';
            prepTimeEl.value = product.prep_time || '';
            ingredientsEl.value = product.ingredients || '';
            badgeEl.value = product.badge || 'none';
            var badgeTextEl = document.getElementById('rmm-food-form-badge-text');
            var badgeIconEl = document.getElementById('rmm-food-form-badge-icon');
            var badgeImageEl = document.getElementById('rmm-food-form-badge-image');
            var badgeExtraBox = document.getElementById('rmm-badge-extra-settings');
            var badgePreviewBox = document.getElementById('rmm-badge-img-preview');
            var badgeImgTag = document.getElementById('rmm-badge-img-tag');

            if (badgeTextEl) badgeTextEl.value = product.badge_text || '';
            if (badgeIconEl) badgeIconEl.value = product.badge_custom_icon || '';
            if (badgeImageEl) badgeImageEl.value = product.badge_image || '';
            if (badgeExtraBox) badgeExtraBox.style.display = (badgeEl.value !== 'none') ? 'block' : 'none';
            if (badgeImageEl && badgeImageEl.value && badgePreviewBox && badgeImgTag) {
                badgeImgTag.src = badgeImageEl.value;
                badgePreviewBox.style.display = 'inline-flex';
            } else if (badgePreviewBox) {
                badgePreviewBox.style.display = 'none';
            }

            if (categorySelect && product.category_id) {
                categorySelect.value = product.category_id;
            }

            // Populate multilingual translations
            document.querySelectorAll('.rmm-trans-name-input').forEach(function(inp) {
                var lang = inp.getAttribute('data-lang');
                var descInp = document.querySelector('.rmm-trans-desc-input[data-lang="' + lang + '"]');
                var ingInp = document.querySelector('.rmm-trans-ingredients-input[data-lang="' + lang + '"]');
                var trans = (product.translations && product.translations[lang]) ? product.translations[lang] : null;
                if (trans) {
                    inp.value = trans.name || '';
                    if (descInp) descInp.value = trans.desc || '';
                    if (ingInp) ingInp.value = trans.ingredients || '';
                } else if (lang === 'en') {
                    inp.value = product.name_en || '';
                    if (descInp) descInp.value = '';
                    if (ingInp) ingInp.value = '';
                } else {
                    inp.value = '';
                    if (descInp) descInp.value = '';
                    if (ingInp) ingInp.value = '';
                }
            });

            // Populate images
            state.currentMainImage = (product.image_id && product.image) ? { id: product.image_id, url: product.image } : { id: 0, url: '' };
            state.currentGallery = (product.gallery && Array.isArray(product.gallery)) ? product.gallery.map(function(g){ return { id: g.id, url: g.url }; }) : [];
            renderMainImageUI();
            renderGalleryUI();
        } else {
            titleEl.textContent = 'افزودن غذای جدید به منو';
            idEl.value = 0;
            nameEl.value = '';
            if (descEl) descEl.value = '';
            priceEl.value = '';
            salePriceEl.value = '';
            caloriesEl.value = '';
            prepTimeEl.value = '';
            ingredientsEl.value = '';
            badgeEl.value = 'none';

            var badgeTextEl = document.getElementById('rmm-food-form-badge-text');
            var badgeIconEl = document.getElementById('rmm-food-form-badge-icon');
            var badgeImageEl = document.getElementById('rmm-food-form-badge-image');
            var badgeExtraBox = document.getElementById('rmm-badge-extra-settings');
            var badgePreviewBox = document.getElementById('rmm-badge-img-preview');
            if (badgeTextEl) badgeTextEl.value = '';
            if (badgeIconEl) badgeIconEl.value = '';
            if (badgeImageEl) badgeImageEl.value = '';
            if (badgeExtraBox) badgeExtraBox.style.display = 'none';
            if (badgePreviewBox) badgePreviewBox.style.display = 'none';

            // Clear multilingual translations
            document.querySelectorAll('.rmm-trans-name-input').forEach(function(inp) { inp.value = ''; });
            document.querySelectorAll('.rmm-trans-desc-input').forEach(function(inp) { inp.value = ''; });
            document.querySelectorAll('.rmm-trans-ingredients-input').forEach(function(inp) { inp.value = ''; });

            // Clear images
            state.currentMainImage = { id: 0, url: '' };
            state.currentGallery = [];
            renderMainImageUI();
            renderGalleryUI();
        }

        foodModal.style.display = 'flex';
        nameEl.focus();
    }

    // --- Image & Gallery Upload UI Handlers --- //
    state.currentMainImage = { id: 0, url: '' };
    state.currentGallery = [];

    function renderMainImageUI() {
        var promptEl = document.getElementById('rmm-main-upload-prompt');
        var wrapEl = document.getElementById('rmm-main-preview-wrap');
        var imgEl = document.getElementById('rmm-main-image-preview');
        var idInp = document.getElementById('rmm-food-form-image-id');

        if (state.currentMainImage && state.currentMainImage.url) {
            if (imgEl) imgEl.src = state.currentMainImage.url;
            if (wrapEl) wrapEl.style.display = 'block';
            if (promptEl) promptEl.style.display = 'none';
            if (idInp) idInp.value = state.currentMainImage.id;
        } else {
            if (imgEl) imgEl.src = '';
            if (wrapEl) wrapEl.style.display = 'none';
            if (promptEl) {
                promptEl.style.display = 'flex';
                promptEl.innerHTML = '<span class="upload-icon">📷</span>' +
                    '<span class="upload-text">برای انتخاب تصویر اصلی کلیک کنید یا عکس را بکشید اینجا</span>' +
                    '<span class="upload-subtext">فرمت‌های مجاز: JPG, PNG, WEBP (حداکثر ۵ مگابایت)</span>';
            }
            if (idInp) idInp.value = 0;
        }
    }

    function renderGalleryUI() {
        var stripEl = document.getElementById('rmm-gallery-grid-strip');
        if (!stripEl) return;

        if (!state.currentGallery || !state.currentGallery.length) {
            stripEl.innerHTML = '<div class="rmm-gallery-empty-hint">عکسی در آلبوم ثبت نشده است. این تصاویر در پاپ‌آپ به عنوان اسلایدر نمایش داده می‌شوند.</div>';
            return;
        }

        var html = '';
        state.currentGallery.forEach(function (item, idx) {
            html += '<div class="rmm-gallery-thumb-card" data-idx="' + idx + '">' +
                '<img src="' + escapeHtml(item.url) + '" class="rmm-gallery-thumb-img" alt="عکس آلبوم">' +
                '<button type="button" class="rmm-gallery-del-btn" data-idx="' + idx + '" title="حذف از آلبوم">&times;</button>' +
                '<span class="rmm-gallery-badge-order">' + (idx + 1) + '</span>' +
                '</div>';
        });

        stripEl.innerHTML = html;
    }

    function uploadImageFile(file) {
        var formData = new FormData();
        formData.append('file', file);

        return fetch(config.rest_url + 'upload-image', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': config.nonce
            },
            body: formData
        }).then(function (res) {
            return res.json();
        }).catch(function (err) {
            console.error('Image upload failed:', err);
            return { success: false, message: 'خطا در ارتباط با سرور' };
        });
    }

    // Main image file selection
    var mainFileInput = document.getElementById('rmm-main-image-file');
    var mainDropArea = document.getElementById('rmm-main-image-drop');
    var changeMainBtn = document.getElementById('rmm-btn-change-main-img');
    var removeMainBtn = document.getElementById('rmm-btn-remove-main-img');

    if (mainDropArea) {
        mainDropArea.addEventListener('click', function (e) {
            if (e.target && (e.target.closest('#rmm-btn-remove-main-img') || e.target.closest('#rmm-btn-change-main-img'))) {
                return;
            }
            if (!state.currentMainImage || !state.currentMainImage.url) {
                if (mainFileInput) mainFileInput.click();
            }
        });

        mainDropArea.addEventListener('dragover', function (e) {
            e.preventDefault();
            mainDropArea.classList.add('drag-active');
        });

        mainDropArea.addEventListener('dragleave', function (e) {
            e.preventDefault();
            mainDropArea.classList.remove('drag-active');
        });

        mainDropArea.addEventListener('drop', function (e) {
            e.preventDefault();
            mainDropArea.classList.remove('drag-active');
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                handleMainFileUpload(e.dataTransfer.files[0]);
            }
        });
    }

    if (changeMainBtn) {
        changeMainBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            if (mainFileInput) mainFileInput.click();
        });
    }

    if (removeMainBtn) {
        removeMainBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            state.currentMainImage = { id: 0, url: '' };
            renderMainImageUI();
            showToast('تصویر اصلی حذف شد.', 'info');
        });
    }

    if (mainFileInput) {
        mainFileInput.addEventListener('change', function () {
            if (this.files && this.files.length) {
                handleMainFileUpload(this.files[0]);
                this.value = '';
            }
        });
    }

    function handleMainFileUpload(file) {
        var promptEl = document.getElementById('rmm-main-upload-prompt');
        if (promptEl) {
            promptEl.style.display = 'flex';
            promptEl.innerHTML = '<span class="upload-icon">⏳</span><span class="upload-text">در حال بارگذاری و ذخیره در رسانه وردپرس...</span>';
        }
        var wrapEl = document.getElementById('rmm-main-preview-wrap');
        if (wrapEl) wrapEl.style.display = 'none';

        uploadImageFile(file).then(function (res) {
            if (res && res.success && res.id) {
                state.currentMainImage = { id: res.id, url: res.url };
                renderMainImageUI();
                showToast('تصویر اصلی غذا با موفقیت بارگذاری شد.', 'success');
            } else {
                renderMainImageUI();
                showToast((res && res.message) ? res.message : 'خطا در بارگذاری تصویر', 'error');
            }
        });
    }

    // Gallery images selection
    var addGalleryBtn = document.getElementById('rmm-btn-add-gallery');
    var galleryFileInput = document.getElementById('rmm-gallery-image-file');

    if (addGalleryBtn && galleryFileInput) {
        addGalleryBtn.addEventListener('click', function (e) {
            e.preventDefault();
            galleryFileInput.click();
        });

        galleryFileInput.addEventListener('change', function () {
            if (!this.files || !this.files.length) return;
            var files = Array.from(this.files);
            this.value = '';

            showToast('در حال بارگذاری ' + files.length + ' تصویر در آلبوم...', 'info');

            var uploadPromises = files.map(function (f) {
                return uploadImageFile(f);
            });

            Promise.all(uploadPromises).then(function (results) {
                var addedCount = 0;
                results.forEach(function (res) {
                    if (res && res.success && res.id) {
                        state.currentGallery.push({ id: res.id, url: res.url });
                        addedCount++;
                    }
                });

                renderGalleryUI();
                if (addedCount > 0) {
                    showToast(addedCount + ' تصویر جدید به آلبوم اسلایدر اضافه شد.', 'success');
                }
            });
        });
    }

    // Remove single gallery image
    document.addEventListener('click', function (e) {
        if (e.target && e.target.classList.contains('rmm-gallery-del-btn')) {
            e.preventDefault();
            e.stopPropagation();
            var idx = parseInt(e.target.getAttribute('data-idx'), 10);
            if (!isNaN(idx) && idx >= 0 && idx < state.currentGallery.length) {
                state.currentGallery.splice(idx, 1);
                renderGalleryUI();
                showToast('عکس از آلبوم حذف شد.', 'info');
            }
        }
    });

    function closeFoodModal() {
        if (foodModal) foodModal.style.display = 'none';
    }

    if (addFoodBtn) {
        addFoodBtn.addEventListener('click', function () {
            openFoodModal(null);
        });
    }

    var mobileFabAddFood = document.getElementById('rmm-mobile-fab-add-food');
    if (mobileFabAddFood) {
        mobileFabAddFood.addEventListener('click', function (e) {
            e.preventDefault();
            openFoodModal(null);
        });
    }

    if (closeFoodModalBtn) closeFoodModalBtn.addEventListener('click', closeFoodModal);
    if (cancelFoodBtn) cancelFoodBtn.addEventListener('click', closeFoodModal);

    // Close on backdrop click
    if (foodModal) {
        foodModal.addEventListener('click', function (e) {
            if (e.target === foodModal) closeFoodModal();
        });
    }

    window.rmmEditFood = function (productId) {
        var found = state.products.find(function (p) { return p.id == productId; });
        if (found) {
            openFoodModal(found);
        }
    };

    // Badge Customization Event Listeners
    var badgeSelectEl = document.getElementById('rmm-food-form-badge');
    var badgeExtraBox = document.getElementById('rmm-badge-extra-settings');
    var badgeUploadBtn = document.getElementById('rmm-badge-upload-btn');
    var badgeFileInput = document.getElementById('rmm-badge-file-input');
    var badgeImageEl = document.getElementById('rmm-food-form-badge-image');
    var badgePreviewBox = document.getElementById('rmm-badge-img-preview');
    var badgeImgTag = document.getElementById('rmm-badge-img-tag');
    var badgeImgRemove = document.getElementById('rmm-badge-img-remove');

    if (badgeSelectEl && badgeExtraBox) {
        badgeSelectEl.addEventListener('change', function () {
            badgeExtraBox.style.display = (this.value !== 'none') ? 'block' : 'none';
        });
    }

    if (badgeUploadBtn && badgeFileInput) {
        badgeUploadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            badgeFileInput.click();
        });

        badgeFileInput.addEventListener('change', function () {
            if (!this.files || !this.files.length) return;
            var file = this.files[0];
            badgeUploadBtn.disabled = true;
            badgeUploadBtn.textContent = '⏳...';

            uploadImageFile(file).then(function (res) {
                badgeUploadBtn.disabled = false;
                badgeUploadBtn.textContent = '📷 آپلود';
                if (res && res.success && res.url) {
                    if (badgeImageEl) badgeImageEl.value = res.url;
                    if (badgeImgTag) badgeImgTag.src = res.url;
                    if (badgePreviewBox) badgePreviewBox.style.display = 'inline-flex';
                    showToast('تصویر نشان با موفقیت بارگذاری شد.', 'success');
                } else {
                    showToast((res && res.message) ? res.message : 'خطا در بارگذاری تصویر نشان', 'error');
                }
            });
        });
    }

    if (badgeImgRemove && badgeImageEl && badgePreviewBox) {
        badgeImgRemove.addEventListener('click', function (e) {
            e.preventDefault();
            badgeImageEl.value = '';
            if (badgeImgTag) badgeImgTag.src = '';
            badgePreviewBox.style.display = 'none';
        });
    }

    if (badgeImageEl) {
        badgeImageEl.addEventListener('input', function () {
            var val = this.value.trim();
            if (val && badgePreviewBox && badgeImgTag) {
                badgeImgTag.src = val;
                badgePreviewBox.style.display = 'inline-flex';
            } else if (badgePreviewBox) {
                badgePreviewBox.style.display = 'none';
            }
        });
    }

    window.rmmDeleteFood = function (productId) {
        if (!confirm('آیا از حذف این غذا مطمئن هستید؟ این غذا به سطل زباله منتقل می‌شود.')) return;
        apiRequest('delete-product', 'POST', { product_id: productId }).then(function (res) {
            if (res && res.success) {
                showToast(res.message, 'success');
                fetchProducts();
            } else {
                showToast('خطا در حذف غذا', 'error');
            }
        });
    };

    // 13. AI Auto-Translate Button in Modal
    var aiTranslateBtn = document.getElementById('rmm-ai-translate-btn');
    if (aiTranslateBtn) {
        aiTranslateBtn.addEventListener('click', function () {
            var nameFa = document.getElementById('rmm-food-form-name').value.trim();
            var descFa = document.getElementById('rmm-food-form-desc') ? document.getElementById('rmm-food-form-desc').value.trim() : '';
            var ingFa = document.getElementById('rmm-food-form-ingredients').value.trim();

            if (!nameFa && !descFa && !ingFa) {
                showToast('لطفاً ابتدا نام، توضیحات یا ترکیبات را به فارسی وارد کنید.', 'error');
                return;
            }

            var btnText = document.getElementById('rmm-ai-btn-text');
            var origText = btnText ? btnText.textContent : 'ترجمه خودکار هوشمند';
            if (btnText) btnText.textContent = 'در حال ترجمه با هوش مصنوعی...';
            aiTranslateBtn.disabled = true;

            var langRows = document.querySelectorAll('.rmm-lang-trans-row');
            var promises = [];

            langRows.forEach(function (row) {
                var langCode = row.getAttribute('data-lang');
                var nameInput = row.querySelector('.rmm-trans-name-input');
                var descInput = row.querySelector('.rmm-trans-desc-input');
                var ingInput = row.querySelector('.rmm-trans-ingredients-input');

                var p = apiRequest('ai-translate', 'POST', {
                    texts: { name: nameFa, desc: descFa, ingredients: ingFa },
                    target_lang: langCode
                }).then(function (res) {
                    if (res && res.success && res.translated) {
                        if (nameInput && res.translated.name && (langCode === 'fa' || res.translated.name !== nameFa)) {
                            nameInput.value = res.translated.name;
                        }
                        if (descInput && res.translated.desc && (langCode === 'fa' || res.translated.desc !== descFa)) {
                            descInput.value = res.translated.desc;
                        }
                        if (ingInput && res.translated.ingredients && (langCode === 'fa' || res.translated.ingredients !== ingFa)) {
                            ingInput.value = res.translated.ingredients;
                        }
                        return true;
                    } else if (res && !res.success) {
                        throw new Error(res.message || 'خطا در ترجمه');
                    }
                    return false;
                }).catch(function (err) {
                    console.error('Translation error for ' + langCode, err);
                    return Promise.reject(err);
                });

                promises.push(p);
            });

            Promise.allSettled(promises).then(function (results) {
                aiTranslateBtn.disabled = false;
                var succeeded = results.filter(function (r) { return r.status === 'fulfilled' && r.value === true; }).length;
                var failed = results.filter(function (r) { return r.status === 'rejected'; });

                if (succeeded > 0 && failed.length === 0) {
                    if (btnText) btnText.textContent = '✨ ترجمه انجام شد!';
                    showToast('ترجمه‌ها به صورت هوشمند تکمیل شدند.', 'success');
                } else if (succeeded > 0 && failed.length > 0) {
                    if (btnText) btnText.textContent = '⚠️ ترجمه ناقص';
                    showToast('برخی زبان‌ها با خطا مواجه شدند، اما باقی ترجمه شدند.', 'info');
                } else {
                    if (btnText) btnText.textContent = '❌ خطا در ترجمه';
                    var errMsg = (failed[0] && failed[0].reason && failed[0].reason.message) ? failed[0].reason.message : 'خطا در برقراری ارتباط با سرویس هوش مصنوعی.';
                    showToast(errMsg, 'error');
                }

                setTimeout(function () {
                    if (btnText) btnText.textContent = origText;
                }, 3000);
            });
        });
    }

    // 14. Save Food Form Submission
    if (foodForm) {
        foodForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var submitBtn = document.getElementById('rmm-save-food-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span>⏳</span> <span>در حال ذخیره...</span>';

            var branchesChecked = [];
            document.querySelectorAll('.rmm-branch-checkbox:checked').forEach(function (cb) {
                branchesChecked.push(cb.value);
            });

            var translations = {};
            document.querySelectorAll('.rmm-trans-name-input').forEach(function (inp) {
                var lang = inp.getAttribute('data-lang');
                var descInp = document.querySelector('.rmm-trans-desc-input[data-lang="' + lang + '"]');
                var ingInp = document.querySelector('.rmm-trans-ingredients-input[data-lang="' + lang + '"]');
                translations[lang] = {
                    name: inp.value.trim(),
                    desc: descInp ? descInp.value.trim() : '',
                    ingredients: ingInp ? ingInp.value.trim() : ''
                };
            });

            var payload = {
                id: parseInt(document.getElementById('rmm-food-form-id').value, 10) || 0,
                name: document.getElementById('rmm-food-form-name').value.trim(),
                description: document.getElementById('rmm-food-form-desc') ? document.getElementById('rmm-food-form-desc').value.trim() : '',
                name_en: (translations['en'] && translations['en'].name) ? translations['en'].name : '',
                image_id: (state.currentMainImage && state.currentMainImage.id) ? parseInt(state.currentMainImage.id, 10) : 0,
                gallery_ids: (state.currentGallery && Array.isArray(state.currentGallery)) ? state.currentGallery.map(function (item) { return item.id; }) : [],
                category_id: parseInt(categorySelect ? categorySelect.value : 0, 10),
                regular_price: parseFloat(document.getElementById('rmm-food-form-price').value.replace(/,/g, '').trim()) || 0,
                sale_price: document.getElementById('rmm-food-form-sale-price').value.replace(/,/g, '').trim() ? parseFloat(document.getElementById('rmm-food-form-sale-price').value.replace(/,/g, '').trim()) : null,
                calories: document.getElementById('rmm-food-form-calories').value,
                prep_time: parseInt(document.getElementById('rmm-food-form-prep-time').value, 10) || 0,
                ingredients: document.getElementById('rmm-food-form-ingredients').value,
                badge: document.getElementById('rmm-food-form-badge').value,
                badge_text: (document.getElementById('rmm-food-form-badge-text') ? document.getElementById('rmm-food-form-badge-text').value.trim() : ''),
                badge_custom_icon: (document.getElementById('rmm-food-form-badge-icon') ? document.getElementById('rmm-food-form-badge-icon').value.trim() : ''),
                badge_image: (document.getElementById('rmm-food-form-badge-image') ? document.getElementById('rmm-food-form-badge-image').value.trim() : ''),
                branches: branchesChecked,
                translations: translations
            };

            apiRequest('save-product', 'POST', payload).then(function (res) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<span>💾</span> <span>ذخیره غذا در منو</span>';
                if (res && res.success) {
                    showToast(res.message, 'success');
                    closeFoodModal();
                    fetchProducts();
                } else {
                    showToast((res && res.message) ? res.message : 'خطا در ذخیره غذا', 'error');
                }
            });
        });
    }

    // ==========================================
    // 7. CATEGORY MANAGEMENT & AI TRANSLATION
    // ==========================================
    var catModal = document.getElementById('rmm-category-modal');
    var catEditModal = document.getElementById('rmm-category-edit-modal');
    var manageCatsBtn = document.getElementById('rmm-manage-categories-btn');
    var closeCatModalBtn = document.getElementById('rmm-close-cat-modal-btn');
    var closeCatEditModalBtn = document.getElementById('rmm-close-cat-edit-modal-btn');
    var cancelCatBtn = document.getElementById('rmm-cancel-cat-btn');
    var createNewCatBtn = document.getElementById('rmm-create-new-cat-btn');
    var saveCatOrderBtn = document.getElementById('rmm-save-cat-order-btn');
    var catListContainer = document.getElementById('rmm-category-list-container');
    var catForm = document.getElementById('rmm-category-form');
    var catAiTranslateBtn = document.getElementById('rmm-cat-ai-translate-btn');

    // Category Image Dropzone elements
    var catImageDropzone = document.getElementById('rmm-cat-image-dropzone');
    var catImageFileInput = document.getElementById('rmm-cat-image-file-input');
    var catDropzoneEmpty = document.getElementById('rmm-cat-dropzone-empty');
    var catDropzonePreview = document.getElementById('rmm-cat-dropzone-preview');
    var catPreviewImg = document.getElementById('rmm-cat-preview-img');
    var catRemoveImgBtn = document.getElementById('rmm-cat-remove-img-btn');
    var catThumbIdInput = document.getElementById('rmm-cat-form-thumb-id');

    function renderCategoryList() {
        if (!catListContainer) return;
        if (!state.categories || !state.categories.length) {
            catListContainer.innerHTML = '<div style="text-align:center; padding:30px; color:var(--rmm-text-muted);">هنوز هیچ دسته‌بندی ایجاد نشده است.</div>';
            return;
        }

        var html = '';
        var total = state.categories.length;
        state.categories.forEach(function (cat, index) {
            var thumbSrc = cat.thumbnail_url || 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'%3E%3Cpath d=\'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5-7l-3 3.72L9 13l-3 4h12l-4-5z\'/ %3E%3C/svg%3E';
            var isFirst = (index === 0);
            var isLast = (index === total - 1);

            html += '<div class="rmm-cat-item-card" data-id="' + escapeHtml(cat.id) + '" data-index="' + index + '">' +
                '<div class="rmm-cat-order-col">' +
                '<button type="button" class="rmm-btn-order-move rmm-btn-order-up" data-id="' + escapeHtml(cat.id) + '" title="انتقال به بالا (اولویت بیشتر)" ' + (isFirst ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : '') + '>▲</button>' +
                '<span class="rmm-cat-order-num" title="رتبه اولویت">#' + (index + 1) + '</span>' +
                '<button type="button" class="rmm-btn-order-move rmm-btn-order-down" data-id="' + escapeHtml(cat.id) + '" title="انتقال به پایین (اولویت کمتر)" ' + (isLast ? 'disabled style="opacity:0.25;cursor:not-allowed;"' : '') + '>▼</button>' +
                '</div>' +
                '<div class="rmm-cat-item-info">' +
                '<img src="' + escapeHtml(thumbSrc) + '" class="rmm-cat-item-thumb" alt="' + escapeHtml(cat.name) + '">' +
                '<div class="rmm-cat-item-details">' +
                '<h4>' + escapeHtml(cat.name) + ' <span class="rmm-cat-priority-pill">اولویت ' + (index + 1) + '</span></h4>' +
                (cat.description ? '<p>' + escapeHtml(cat.description) + '</p>' : '') +
                '</div>' +
                '</div>' +
                '<div class="rmm-cat-item-actions">' +
                '<span class="rmm-cat-item-count">' + escapeHtml(cat.count || 0) + ' غذا</span>' +
                '<button type="button" class="rmm-btn-cat-action edit rmm-edit-cat-btn" data-id="' + escapeHtml(cat.id) + '"><svg width="15" height="15" style="vertical-align:middle; display:inline-block;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M15 22.75H9C3.57 22.75 1.25 20.43 1.25 15V9C1.25 3.57 3.57 1.25 9 1.25H11C11.41 1.25 11.75 1.59 11.75 2C11.75 2.41 11.41 2.75 11 2.75H9C4.39 2.75 2.75 4.39 2.75 9V15C2.75 19.61 4.39 21.25 9 21.25H15C19.61 21.25 21.25 19.61 21.25 15V13C21.25 12.59 21.59 12.25 22 12.25C22.41 12.25 22.75 12.59 22.75 13V15C22.75 20.43 20.43 22.75 15 22.75Z"/> <path d="M8.50008 17.6905C7.89008 17.6905 7.33008 17.4705 6.92008 17.0705C6.43008 16.5805 6.22008 15.8705 6.33008 15.1205L6.76008 12.1105C6.84008 11.5305 7.22008 10.7805 7.63008 10.3705L15.5101 2.49055C17.5001 0.500547 19.5201 0.500547 21.5101 2.49055C22.6001 3.58055 23.0901 4.69055 22.9901 5.80055C22.9001 6.70055 22.4201 7.58055 21.5101 8.48055L13.6301 16.3605C13.2201 16.7705 12.4701 17.1505 11.8901 17.2305L8.88008 17.6605C8.75008 17.6905 8.62008 17.6905 8.50008 17.6905ZM16.5701 3.55055L8.69008 11.4305C8.50008 11.6205 8.28008 12.0605 8.24008 12.3205L7.81008 15.3305C7.77008 15.6205 7.83008 15.8605 7.98008 16.0105C8.13008 16.1605 8.37008 16.2205 8.66008 16.1805L11.6701 15.7505C11.9301 15.7105 12.3801 15.4905 12.5601 15.3005L20.4401 7.42055C21.0901 6.77055 21.4301 6.19055 21.4801 5.65055C21.5401 5.00055 21.2001 4.31055 20.4401 3.54055C18.8401 1.94055 17.7401 2.39055 16.5701 3.55055Z"/> <path d="M19.8501 9.83027C19.7801 9.83027 19.7101 9.82027 19.6501 9.80027C17.0201 9.06027 14.9301 6.97027 14.1901 4.34027C14.0801 3.94027 14.3101 3.53027 14.7101 3.41027C15.1101 3.30027 15.5201 3.53027 15.6301 3.93027C16.2301 6.06027 17.9201 7.75027 20.0501 8.35027C20.4501 8.46027 20.6801 8.88027 20.5701 9.28027C20.4801 9.62027 20.1801 9.83027 19.8501 9.83027Z"/> </svg> ویرایش</button>' +
                '<button type="button" class="rmm-btn-cat-action delete rmm-delete-cat-btn" data-id="' + escapeHtml(cat.id) + '"><svg width="15" height="15" style="vertical-align:middle; display:inline-block;" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg"> <path d="M13.3901 18.1091C13.2001 18.1091 13.0101 18.0391 12.8601 17.8891L10.1001 15.1291C9.81007 14.8391 9.81007 14.3591 10.1001 14.0691C10.3901 13.7791 10.8701 13.7791 11.1601 14.0691L13.9201 16.8291C14.2101 17.1191 14.2101 17.5991 13.9201 17.8891C13.7801 18.0391 13.5901 18.1091 13.3901 18.1091Z"/> <path d="M10.61 18.1403C10.42 18.1403 10.23 18.0703 10.08 17.9203C9.79005 17.6303 9.79005 17.1503 10.08 16.8603L12.84 14.1003C13.13 13.8103 13.61 13.8103 13.9 14.1003C14.19 14.3903 14.19 14.8703 13.9 15.1603L11.14 17.9203C10.99 18.0703 10.8 18.1403 10.61 18.1403Z"/> <path d="M5.18988 6.37945C4.99988 6.37945 4.79988 6.29945 4.65988 6.15945C4.36988 5.86945 4.36988 5.38945 4.65988 5.09945L8.28988 1.46945C8.57988 1.17945 9.05988 1.17945 9.34988 1.46945C9.63988 1.75945 9.63988 2.23945 9.34988 2.52945L5.71988 6.15945C5.56988 6.29945 5.37988 6.37945 5.18988 6.37945Z"/> <path d="M18.8101 6.37945C18.6201 6.37945 18.4301 6.30945 18.2801 6.15945L14.6501 2.52945C14.3601 2.23945 14.3601 1.75945 14.6501 1.46945C14.9401 1.17945 15.4201 1.17945 15.7101 1.46945L19.3401 5.09945C19.6301 5.38945 19.6301 5.86945 19.3401 6.15945C19.2001 6.29945 19.0001 6.37945 18.8101 6.37945Z"/> <path d="M20.21 10.5996C20.14 10.5996 20.07 10.5996 20 10.5996H19.77H4C3.3 10.6096 2.5 10.6096 1.92 10.0296C1.46 9.57961 1.25 8.87961 1.25 7.84961C1.25 5.09961 3.26 5.09961 4.22 5.09961H19.78C20.74 5.09961 22.75 5.09961 22.75 7.84961C22.75 8.88961 22.54 9.57961 22.08 10.0296C21.56 10.5496 20.86 10.5996 20.21 10.5996ZM4.22 9.09961H20.01C20.46 9.10961 20.88 9.10961 21.02 8.96961C21.09 8.89961 21.24 8.65961 21.24 7.84961C21.24 6.71961 20.96 6.59961 19.77 6.59961H4.22C3.03 6.59961 2.75 6.71961 2.75 7.84961C2.75 8.65961 2.91 8.89961 2.97 8.96961C3.11 9.09961 3.54 9.09961 3.98 9.09961H4.22Z"/> <path d="M14.89 22.7507H8.86C5.28 22.7507 4.48 20.6207 4.17 18.7707L2.76 10.1207C2.69 9.71073 2.97 9.33073 3.38 9.26073C3.79 9.19073 4.17 9.47073 4.24 9.88073L5.65 18.5207C5.94 20.2907 6.54 21.2507 8.86 21.2507H14.89C17.46 21.2507 17.75 20.3507 18.08 18.6107L19.76 9.86073C19.84 9.45073 20.23 9.18073 20.64 9.27073C21.05 9.35073 21.31 9.74073 21.23 10.1507L19.55 18.9007C19.16 20.9307 18.51 22.7507 14.89 22.7507Z"/> </svg> حذف</button>' +
                '</div>' +
                '</div>';
        });

        catListContainer.innerHTML = html;
    }

    function saveCategoryOrder(isManualClick, customMsg) {
        if (!state.categories || !state.categories.length) return;
        var catIds = state.categories.map(function (c) { return c.id; });

        if (saveCatOrderBtn) saveCatOrderBtn.disabled = true;

        apiRequest('reorder-categories', 'POST', { category_ids: catIds }).then(function (res) {
            if (res && res.success) {
                showToast(customMsg || res.message || 'ترتیب اولویت دسته‌بندی‌ها با موفقیت ذخیره شد.', 'success');
                if (res.categories && res.categories.length) {
                    state.categories = res.categories;
                    renderCategoryChips();
                }
            } else {
                showToast('خطا در ذخیره‌سازی اولویت دسته‌بندی‌ها!', 'error');
            }
        }).catch(function (err) {
            showToast('خطای ارتباط با سرور در ذخیره اولویت‌ها', 'error');
        }).finally(function () {
            if (saveCatOrderBtn) saveCatOrderBtn.disabled = false;
        });
    }

    if (saveCatOrderBtn) {
        saveCatOrderBtn.addEventListener('click', function () {
            saveCategoryOrder(true);
        });
    }

    function openCategoryEditModal(cat) {
        if (!catEditModal) return;
        var idEl = document.getElementById('rmm-cat-form-id');
        var nameEl = document.getElementById('rmm-cat-form-name');
        var descEl = document.getElementById('rmm-cat-form-desc');
        var titleEl = document.getElementById('rmm-cat-edit-modal-title');

        if (cat) {
            titleEl.textContent = 'ویرایش دسته‌بندی: ' + cat.name;
            idEl.value = cat.id;
            nameEl.value = cat.name;
            descEl.value = cat.description || '';
            var orderEl = document.getElementById('rmm-cat-form-order');
            if (orderEl) orderEl.value = cat.priority || cat.order || '';
            catThumbIdInput.value = cat.thumbnail_id || 0;

            if (cat.thumbnail_url) {
                catPreviewImg.src = cat.thumbnail_url;
                catDropzoneEmpty.style.display = 'none';
                catDropzonePreview.style.display = 'block';
            } else {
                catPreviewImg.src = '';
                catDropzoneEmpty.style.display = 'flex';
                catDropzonePreview.style.display = 'none';
            }

            // Populate translations
            var trans = cat.translations || {};
            document.querySelectorAll('.rmm-cat-trans-name').forEach(function (inp) {
                var lang = inp.getAttribute('data-lang');
                inp.value = (trans[lang] && trans[lang].name) ? trans[lang].name : '';
            });
            document.querySelectorAll('.rmm-cat-trans-desc').forEach(function (inp) {
                var lang = inp.getAttribute('data-lang');
                inp.value = (trans[lang] && trans[lang].desc) ? trans[lang].desc : '';
            });
        } else {
            titleEl.textContent = 'تعریف دسته‌بندی جدید';
            idEl.value = 0;
            nameEl.value = '';
            descEl.value = '';
            var orderElNew = document.getElementById('rmm-cat-form-order');
            if (orderElNew) orderElNew.value = (state.categories ? state.categories.length + 1 : 1);
            catThumbIdInput.value = 0;
            catPreviewImg.src = '';
            catDropzoneEmpty.style.display = 'flex';
            catDropzonePreview.style.display = 'none';

            document.querySelectorAll('.rmm-cat-trans-name, .rmm-cat-trans-desc').forEach(function (inp) {
                inp.value = '';
            });
        }

        catEditModal.style.display = 'flex';
    }

    if (manageCatsBtn) {
        manageCatsBtn.addEventListener('click', function () {
            if (catModal) {
                catModal.style.display = 'flex';
                renderCategoryList();
            }
        });
    }

    if (closeCatModalBtn) {
        closeCatModalBtn.addEventListener('click', function () {
            if (catModal) catModal.style.display = 'none';
        });
    }

    if (catModal) {
        catModal.addEventListener('click', function (e) {
            if (e.target === this) catModal.style.display = 'none';
        });
    }

    if (createNewCatBtn) {
        createNewCatBtn.addEventListener('click', function () {
            openCategoryEditModal(null);
        });
    }

    if (closeCatEditModalBtn) {
        closeCatEditModalBtn.addEventListener('click', function () {
            if (catEditModal) catEditModal.style.display = 'none';
        });
    }

    if (cancelCatBtn) {
        cancelCatBtn.addEventListener('click', function () {
            if (catEditModal) catEditModal.style.display = 'none';
        });
    }

    if (catEditModal) {
        catEditModal.addEventListener('click', function (e) {
            if (e.target === this) catEditModal.style.display = 'none';
        });
    }

    // Category Edit & Delete delegation
    document.addEventListener('click', function (e) {
        var upBtn = e.target.closest('.rmm-btn-order-up');
        if (upBtn) {
            var catId = parseInt(upBtn.getAttribute('data-id'), 10);
            var idx = state.categories.findIndex(function (c) { return c.id === catId; });
            if (idx > 0) {
                var item = state.categories.splice(idx, 1)[0];
                state.categories.splice(idx - 1, 0, item);
                renderCategoryList();
                renderCategoryChips();
                saveCategoryOrder(false, 'اولویت «' + item.name + '» افزایش یافت و ذخیره شد.');
            }
            return;
        }

        var downBtn = e.target.closest('.rmm-btn-order-down');
        if (downBtn) {
            var catId = parseInt(downBtn.getAttribute('data-id'), 10);
            var idx = state.categories.findIndex(function (c) { return c.id === catId; });
            if (idx !== -1 && idx < state.categories.length - 1) {
                var item = state.categories.splice(idx, 1)[0];
                state.categories.splice(idx + 1, 0, item);
                renderCategoryList();
                renderCategoryChips();
                saveCategoryOrder(false, 'اولویت «' + item.name + '» کاهش یافت و ذخیره شد.');
            }
            return;
        }

        var editBtn = e.target.closest('.rmm-edit-cat-btn');
        if (editBtn) {
            var catId = parseInt(editBtn.getAttribute('data-id'), 10);
            var found = state.categories.find(function (c) { return c.id == catId; });
            if (found) openCategoryEditModal(found);
            return;
        }

        var delBtn = e.target.closest('.rmm-delete-cat-btn');
        if (delBtn) {
            var catId = parseInt(delBtn.getAttribute('data-id'), 10);
            if (!confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟ غذاهای این دسته بدون دسته‌بندی خواهند شد.')) {
                return;
            }
            apiRequest('delete-category', 'POST', { id: catId }).then(function (res) {
                if (res && res.success) {
                    showToast('دسته‌بندی با موفقیت حذف شد.', 'success');
                    fetchCategories();
                    fetchProducts();
                    renderCategoryList();
                } else {
                    showToast('خطا در حذف دسته‌بندی!', 'error');
                }
            });
        }
    });

    // Category Image Dropzone handlers (with WebP conversion)
    var catChangeImgBtn = document.getElementById('rmm-cat-change-img-btn');
    if (catChangeImgBtn && catImageFileInput) {
        catChangeImgBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            catImageFileInput.click();
        });
    }
    if (catImageDropzone && catImageFileInput) {
        catImageDropzone.addEventListener('click', function (e) {
            if (e.target.closest('#rmm-cat-remove-img-btn') || e.target.closest('#rmm-cat-change-img-btn')) return;
            catImageFileInput.click();
        });

        catImageDropzone.addEventListener('dragover', function (e) {
            e.preventDefault();
            catImageDropzone.classList.add('dragover');
        });

        catImageDropzone.addEventListener('dragleave', function () {
            catImageDropzone.classList.remove('dragover');
        });

        catImageDropzone.addEventListener('drop', function (e) {
            e.preventDefault();
            catImageDropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files.length) {
                uploadCategoryImage(e.dataTransfer.files[0]);
            }
        });

        catImageFileInput.addEventListener('change', function () {
            if (this.files && this.files.length) {
                uploadCategoryImage(this.files[0]);
            }
        });
    }

    if (catRemoveImgBtn) {
        catRemoveImgBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            catThumbIdInput.value = 0;
            catPreviewImg.src = '';
            catDropzonePreview.style.display = 'none';
            catDropzoneEmpty.style.display = 'flex';
            catImageFileInput.value = '';
        });
    }

    function uploadCategoryImage(file) {
        if (!file.type.match('image.*')) {
            showToast('لطفاً یک فایل تصویری انتخاب نمایید.', 'error');
            return;
        }

        catDropzoneEmpty.innerHTML = '<span class="dropzone-icon">⏳</span><p class="dropzone-text">در حال فشرده‌سازی و تبدیل به WebP...</p>';

        var formData = new FormData();
        formData.append('file', file);

        var url = config.rest_url + 'upload-image';
        fetch(url, {
            method: 'POST',
            headers: {
                'X-WP-Nonce': config.nonce
            },
            body: formData
        }).then(function (res) {
            return res.json();
        }).then(function (data) {
            if (data && data.success && data.id) {
                catThumbIdInput.value = data.id;
                catPreviewImg.src = data.url || data.full_url;
                catDropzoneEmpty.style.display = 'none';
                catDropzonePreview.style.display = 'block';
                showToast('تصویر دسته‌بندی با فرمت بهینه WebP ذخیره شد! 🚀', 'success');
            } else {
                showToast((data && data.message) ? data.message : 'خطا در آپلود تصویر!', 'error');
            }
        }).catch(function (err) {
            showToast('خطا در ارتباط با سرور: ' + (err.message || 'خطای شبکه'), 'error');
        }).finally(function () {
            catDropzoneEmpty.innerHTML = '<span class="dropzone-icon">🖼️</span><p class="dropzone-text"><strong>کلیک کنید</strong> یا تصویر دسته‌بندی را به اینجا بکشید</p><span class="dropzone-upload-btn">📷 انتخاب و آپلود تصویر دسته</span><span class="dropzone-hint">فرمت‌های JPG، PNG یا WebP - با فشرده‌سازی خودکار</span>';
        });
    }

    // AI Smart Translation for Category
    if (catAiTranslateBtn) {
        catAiTranslateBtn.addEventListener('click', function () {
            var name = document.getElementById('rmm-cat-form-name').value.trim();
            var desc = document.getElementById('rmm-cat-form-desc').value.trim();

            if (!name) {
                showToast('لطفاً ابتدا نام دسته‌بندی را وارد نمایید.', 'error');
                return;
            }

            var textPayload = { name: name };
            if (desc) textPayload.desc = desc;

            var sparkle = catAiTranslateBtn.querySelector('.ai-sparkle');
            var loader = catAiTranslateBtn.querySelector('.ai-btn-loader');
            if (sparkle) sparkle.style.display = 'none';
            if (loader) loader.style.display = 'inline-block';
            catAiTranslateBtn.disabled = true;

            var transLangs = config.trans_langs || [];
            var promises = transLangs.map(function (targetLang) {
                return apiRequest('ai-translate', 'POST', {
                    target_lang: targetLang,
                    texts: textPayload
                }).then(function (res) {
                    if (res && res.success && res.translated) {
                        var nameInput = document.querySelector('.rmm-cat-trans-name[data-lang="' + targetLang + '"]');
                        var descInput = document.querySelector('.rmm-cat-trans-desc[data-lang="' + targetLang + '"]');
                        if (nameInput && res.translated.name && (targetLang === 'fa' || res.translated.name !== textPayload.name)) {
                            nameInput.value = res.translated.name;
                        }
                        if (descInput && res.translated.desc && (targetLang === 'fa' || res.translated.desc !== textPayload.desc)) {
                            descInput.value = res.translated.desc;
                        }
                        return true;
                    } else if (res && !res.success) {
                        throw new Error(res.message || 'خطا در ترجمه دسته');
                    }
                    return false;
                });
            });

            Promise.allSettled(promises).then(function (results) {
                var succeeded = results.filter(function (r) { return r.status === 'fulfilled' && r.value === true; }).length;
                var failed = results.filter(function (r) { return r.status === 'rejected'; });

                if (succeeded > 0 && failed.length === 0) {
                    showToast('ترجمه هوشمند دسته با موفقیت انجام شد! ✨', 'success');
                } else if (succeeded > 0 && failed.length > 0) {
                    showToast('برخی از ترجمه‌های دسته با خطا مواجه شدند.', 'info');
                } else {
                    var errMsg = (failed[0] && failed[0].reason && failed[0].reason.message) ? failed[0].reason.message : 'خطا در برقراری ارتباط با سرویس هوش مصنوعی.';
                    showToast(errMsg, 'error');
                }
            }).finally(function () {
                if (sparkle) sparkle.style.display = 'inline-block';
                if (loader) loader.style.display = 'none';
                catAiTranslateBtn.disabled = false;
            });
        });
    }

    // Save Category Form submit
    if (catForm) {
        catForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = parseInt(document.getElementById('rmm-cat-form-id').value, 10) || 0;
            var name = document.getElementById('rmm-cat-form-name').value.trim();
            var desc = document.getElementById('rmm-cat-form-desc').value.trim();
            var thumbId = parseInt(catThumbIdInput.value, 10) || 0;
            var orderInputEl = document.getElementById('rmm-cat-form-order');
            var catOrderVal = orderInputEl ? parseInt(orderInputEl.value, 10) : 0;

            var translations = {};
            document.querySelectorAll('.rmm-cat-trans-name').forEach(function (inp) {
                var lang = inp.getAttribute('data-lang');
                if (!translations[lang]) translations[lang] = {};
                translations[lang].name = inp.value.trim();
            });
            document.querySelectorAll('.rmm-cat-trans-desc').forEach(function (inp) {
                var lang = inp.getAttribute('data-lang');
                if (!translations[lang]) translations[lang] = {};
                translations[lang].desc = inp.value.trim();
            });

            var saveBtn = document.getElementById('rmm-save-cat-btn');
            if (saveBtn) saveBtn.disabled = true;

            apiRequest('save-category', 'POST', {
                id: id,
                name: name,
                description: desc,
                thumbnail_id: thumbId,
                translations: translations,
                order: catOrderVal
            }).then(function (res) {
                if (res && res.success) {
                    showToast(res.message || 'دسته‌بندی با موفقیت ذخیره شد.', 'success');
                    catEditModal.style.display = 'none';
                    fetchCategories();
                    fetchProducts();
                    renderCategoryList();
                } else {
                    showToast((res && res.message) ? res.message : 'خطا در ذخیره‌سازی دسته‌بندی!', 'error');
                }
            }).catch(function (err) {
                showToast('خطا در ذخیره دسته‌بندی: ' + (err.message || 'خطای ناشناخته'), 'error');
            }).finally(function () {
                if (saveBtn) saveBtn.disabled = false;
            });
        });
    }

    // ==========================================
    // 8. BRANCH MANAGEMENT (VIEW, EDIT, CREATE)
    // ==========================================
    var branchModal = document.getElementById('rmm-branch-modal');
    var branchForm = document.getElementById('rmm-branch-form');
    var editCurrentBranchBtn = document.getElementById('rmm-edit-current-branch-btn');
    var createNewBranchBtn = document.getElementById('rmm-create-new-branch-btn');
    var closeBranchModalBtn = document.getElementById('rmm-close-branch-modal-btn');
    var cancelBranchBtn = document.getElementById('rmm-cancel-branch-btn');

    var currentBranchData = null;

    // Immediately resolve current branch data from preloaded config
    if (state.branches && state.branches.length) {
        var preFound = state.branches.find(function (b) { return b.id == state.branchId; }) || state.branches[0];
        if (preFound) {
            currentBranchData = preFound;
            state.branchId = preFound.id;
            updateBranchViewUI(preFound);
        }
    }

    function fetchCurrentBranchDetails() {
        if (!state.branchId && branchSelect && branchSelect.value) {
            state.branchId = parseInt(branchSelect.value, 10);
        }
        var requestedBranchId = state.branchId;
        var requestVersion = ++branchRequestVersions.details;

        apiRequest('branches', 'GET').then(function (res) {
            if (requestVersion !== branchRequestVersions.details || requestedBranchId !== state.branchId) return;
            if (res && res.success && res.branches && res.branches.length) {
                state.branches = res.branches;
                var found = res.branches.find(function (b) { return b.id == state.branchId; });
                if (found) {
                    currentBranchData = found;
                    updateBranchViewUI(found);
                }
            }
        }).catch(function () {});
    }

    function updateBranchViewUI(branch) {
        var nameEl = document.getElementById('rmm-view-branch-name');
        var phoneEl = document.getElementById('rmm-view-branch-phone');
        var hoursEl = document.getElementById('rmm-view-branch-hours');
        var addressEl = document.getElementById('rmm-view-branch-address');

        if (nameEl) nameEl.textContent = branch.name || '-';
        if (phoneEl) phoneEl.textContent = branch.phone || '-';
        if (hoursEl) hoursEl.textContent = branch.hours || '-';
        if (addressEl) addressEl.textContent = branch.address || 'آدرسی ثبت نشده است.';
    }

    function openBranchModal(branch) {
        if (!branchModal) return;
        var idEl = document.getElementById('rmm-branch-form-id');
        var nameEl = document.getElementById('rmm-branch-form-name');
        var phoneEl = document.getElementById('rmm-branch-form-phone');
        var hoursEl = document.getElementById('rmm-branch-form-hours');
        var addrEl = document.getElementById('rmm-branch-form-address');
        var modeEl = document.getElementById('rmm-branch-form-mode');
        var openEl = document.getElementById('rmm-branch-form-open');
        var titleEl = document.getElementById('rmm-branch-modal-title');

        if (branch) {
            titleEl.textContent = 'ویرایش شعبه: ' + branch.name;
            idEl.value = branch.id;
            nameEl.value = branch.name;
            phoneEl.value = branch.phone || '';
            hoursEl.value = branch.hours || '';
            addrEl.value = branch.address || '';
            modeEl.value = branch.mode || 'global';
            openEl.checked = branch.is_open;
        } else {
            titleEl.textContent = 'معرفی و ثبت شعبه جدید';
            idEl.value = 0;
            nameEl.value = '';
            phoneEl.value = '';
            hoursEl.value = '';
            addrEl.value = '';
            modeEl.value = 'global';
            openEl.checked = true;
        }

        branchModal.style.display = 'flex';
    }

    if (editCurrentBranchBtn) {
        editCurrentBranchBtn.addEventListener('click', function () {
            var branchToEdit = currentBranchData;
            if (!branchToEdit && state.branches && state.branches.length) {
                branchToEdit = state.branches.find(function (b) { return b.id == state.branchId; }) || state.branches[0];
            }
            if (!branchToEdit && config.branches && config.branches.length) {
                branchToEdit = config.branches.find(function (b) { return b.id == state.branchId; }) || config.branches[0];
            }

            if (branchToEdit) {
                currentBranchData = branchToEdit;
                openBranchModal(branchToEdit);
            } else {
                apiRequest('branches', 'GET').then(function (res) {
                    if (res && res.success && res.branches && res.branches.length) {
                        state.branches = res.branches;
                        var found = res.branches.find(function (b) { return b.id == state.branchId; }) || res.branches[0];
                        currentBranchData = found;
                        openBranchModal(found);
                    } else {
                        openBranchModal(null);
                    }
                }).catch(function () {
                    openBranchModal(null);
                });
            }
        });
    }

    if (createNewBranchBtn) {
        createNewBranchBtn.addEventListener('click', function () {
            openBranchModal(null);
        });
    }

    if (closeBranchModalBtn) {
        closeBranchModalBtn.addEventListener('click', function () {
            if (branchModal) branchModal.style.display = 'none';
        });
    }

    if (cancelBranchBtn) {
        cancelBranchBtn.addEventListener('click', function () {
            if (branchModal) branchModal.style.display = 'none';
        });
    }

    if (branchModal) {
        branchModal.addEventListener('click', function (e) {
            if (e.target === this) branchModal.style.display = 'none';
        });
    }

    if (branchForm) {
        branchForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var id = parseInt(document.getElementById('rmm-branch-form-id').value, 10) || 0;
            var name = document.getElementById('rmm-branch-form-name').value.trim();
            var phone = document.getElementById('rmm-branch-form-phone').value.trim();
            var hours = document.getElementById('rmm-branch-form-hours').value.trim();
            var address = document.getElementById('rmm-branch-form-address').value.trim();
            var mode = document.getElementById('rmm-branch-form-mode').value;
            var isOpen = document.getElementById('rmm-branch-form-open').checked ? 1 : 0;

            var saveBtn = document.getElementById('rmm-save-branch-btn');
            if (saveBtn) saveBtn.disabled = true;

            apiRequest('save-branch', 'POST', {
                id: id,
                name: name,
                phone: phone,
                hours: hours,
                address: address,
                mode: mode,
                is_open: isOpen
            }).then(function (res) {
                if (res && res.success) {
                    showToast(res.message || 'اطلاعات شعبه با موفقیت ذخیره شد.', 'success');
                    branchModal.style.display = 'none';

                    if (id === 0 && res.branch) {
                        if (branchSelect) {
                            var opt = document.createElement('option');
                            opt.value = res.branch.id;
                            opt.textContent = res.branch.name;
                            branchSelect.appendChild(opt);
                            branchSelect.value = res.branch.id;
                            state.branchId = res.branch.id;
                        }
                    }

                    fetchCurrentBranchDetails();
                    fetchStats();
                    fetchProducts();
                } else {
                    showToast((res && res.message) ? res.message : 'خطا در ذخیره شعبه!', 'error');
                }
            }).catch(function (err) {
                showToast('خطا در ارتباط با سرور: ' + (err.message || 'خطای ناشناخته'), 'error');
            }).finally(function () {
                if (saveBtn) saveBtn.disabled = false;
            });
        });
    }

    // Refresh only the data relevant to the current dashboard view.
    function refreshDashboardData() {
        if (document.hidden || navigator.onLine === false) return;
        fetchOrders();
        fetchStats();
        fetchCurrentBranchDetails();
        if (state.activeTab === 'menu') {
            fetchProducts();
        }
    }

    window.addEventListener('offline', function () {
        setConnectionState(false);
    });

    window.addEventListener('online', function () {
        setConnectionState(true);
        refreshDashboardData();
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            refreshDashboardData();
        }
    });

    // Initial Load
    fetchCategories();
    fetchOrders();
    fetchStats();
    fetchCurrentBranchDetails();

    // Poll orders only while the dashboard is visible and the browser reports a connection.
    setInterval(function () {
        if (!document.hidden && navigator.onLine !== false) {
            fetchOrders();
        }
    }, 10000);

    if (navigator.onLine === false) {
        setConnectionState(false);
    }


    // Live formatting with thousands separators for price inputs
    document.addEventListener('input', function (e) {
        if (e.target && (e.target.classList.contains('rmm-price-input') || e.target.classList.contains('rmm-thousand-sep') || e.target.id === 'rmm-food-form-price' || e.target.id === 'rmm-food-form-sale-price')) {
            var input = e.target;
            var cursor = input.selectionStart;
            var prevLen = input.value.length;
            var raw = input.value.replace(/[^0-9]/g, '');
            if (!raw) {
                input.value = '';
                return;
            }
            var num = parseInt(raw, 10);
            var formatted = isNaN(num) ? '' : num.toLocaleString('en-US');
            input.value = formatted;
            var diff = formatted.length - prevLen;
            if (cursor !== null) {
                var newPos = Math.max(0, cursor + diff);
                input.setSelectionRange(newPos, newPos);
            }
        }
    });

})();
