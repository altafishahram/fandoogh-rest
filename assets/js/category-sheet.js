/**
 * Fondogh Online Menu — Category Sheet
 *
 * Opens the category sheet, closes it (backdrop / close button / handle /
 * Escape / swipe down) and applies the exact same instant category filter
 * used by the classic Category Bar.
 *
 * Delegated events keep it working with Elementor editor re-renders and
 * multiple widget instances on one page.
 */
(function ($) {
    'use strict';

    if (!$) {
        return;
    }

    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ── Helpers ─────────────────────────────────────────────────────── */

    function getRoot(el) {
        return $(el).closest('[data-catsheet-root]');
    }

    function getOverlay($root) {
        return $root.find('.rmm-catsheet-overlay').first();
    }

    function openSheet($root) {
        var $overlay = getOverlay($root);
        if (!$overlay.length || $overlay.hasClass('is-open')) {
            return;
        }

        $root.data('rmm-return-focus', document.activeElement);

        $overlay.prop('hidden', false).attr('aria-hidden', 'false');

        // Force a reflow so the opening transition always runs.
        void $overlay[0].offsetHeight;
        $overlay.addClass('is-open');

        $root.find('[data-catsheet-open]').attr('aria-expanded', 'true');
        $('body').addClass('rmm-catsheet-lock');

        window.setTimeout(function () {
            var $target = $overlay.find('.rmm-catsheet-card.is-active').first();
            if (!$target.length) {
                $target = $overlay.find('.rmm-catsheet-card').first();
            }
            if ($target.length) {
                $target.trigger('focus');
            }
        }, reducedMotion ? 0 : 120);
    }

    function closeSheet($root, restoreFocus) {
        var $overlay = getOverlay($root);
        if (!$overlay.length) {
            return;
        }

        $overlay.removeClass('is-open').attr('aria-hidden', 'true');
        $root.find('[data-catsheet-open]').attr('aria-expanded', 'false');
        $('body').removeClass('rmm-catsheet-lock');

        var $panel = $overlay.find('.rmm-catsheet-panel');
        $panel.css({ transition: '', transform: '' });

        window.setTimeout(function () {
            if (!$overlay.hasClass('is-open')) {
                $overlay.prop('hidden', true);
            }
        }, reducedMotion ? 0 : 240);

        if (restoreFocus !== false) {
            var previous = $root.data('rmm-return-focus');
            if (previous && typeof previous.focus === 'function') {
                try {
                    previous.focus();
                } catch (err) { /* element is gone */ }
            }
        }
    }

    function syncCards(catId) {
        var id = String(catId);

        $('.rmm-catsheet-card').each(function () {
            var $card = $(this);
            var isActive = String($card.data('cat-id')) === id;
            $card.toggleClass('is-active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
        });
    }

    /* ── Filtering ───────────────────────────────────────────────────── */

    function applyCategoryFilter(catId) {
        // Preferred path: reuse the classic Category Bar handler so both
        // widgets stay in sync (it also toggles the pill active state).
        var pill = document.querySelector('.rmm-cat-pill[data-cat-id="' + String(catId) + '"]');
        if (pill) {
            pill.click();
            return;
        }

        // Fallback: identical behaviour when no Category Bar is on the page.
        if (String(catId) === 'all') {
            $('.rmm-category-section').show();
            $('.rmm-food-card').show();
            return;
        }

        $('.rmm-category-section').each(function () {
            var $section = $(this);
            if (String($section.data('cat-id')) === String(catId)) {
                $section.show();
                $section.find('.rmm-food-card').show();
            } else {
                $section.hide();
            }
        });

        $('.rmm-food-card').each(function () {
            var $card = $(this);
            var cats = String($card.data('cats') || '').split(',');
            if (cats.indexOf(String(catId)) !== -1) {
                $card.show();
            } else if (!$card.closest('.rmm-category-section').length) {
                $card.hide();
            }
        });
    }

    /* ── Open / close ────────────────────────────────────────────────── */

    $(document).on('click', '[data-catsheet-open]', function (e) {
        e.preventDefault();
        openSheet(getRoot(this));
    });

    $(document).on('click', '[data-catsheet-close]', function (e) {
        e.preventDefault();
        closeSheet(getRoot(this));
    });

    $(document).on('keydown', function (e) {
        if (e.key !== 'Escape' && e.keyCode !== 27) {
            return;
        }

        var $open = $('.rmm-catsheet-overlay.is-open');
        if (!$open.length) {
            return;
        }

        $open.each(function () {
            closeSheet(getRoot(this));
        });
    });

    /* ── Card selection ──────────────────────────────────────────────── */

    $(document).on('click', '.rmm-catsheet-card', function (e) {
        e.preventDefault();

        var $card = $(this);
        var $root = getRoot(this);
        var catId = $card.data('cat-id');

        if (catId === undefined || catId === null || catId === '') {
            return;
        }

        syncCards(catId);
        applyCategoryFilter(catId);

        if (String($root.data('close-on-select')) === '1') {
            closeSheet($root);
        }
    });

    // Keep sheet cards in sync when the classic bar is used elsewhere.
    $(document).on('click', '.rmm-cat-pill', function () {
        var catId = $(this).data('cat-id');
        if (catId !== undefined) {
            syncCards(catId);
        }
    });

    /* ── Keyboard focus loop inside the open sheet ───────────────────── */

    $(document).on('keydown', function (e) {
        if (e.key !== 'Tab') {
            return;
        }

        var $overlay = $('.rmm-catsheet-overlay.is-open').first();
        if (!$overlay.length) {
            return;
        }

        var $focusable = $overlay.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').filter(':visible');
        if (!$focusable.length) {
            return;
        }

        var first = $focusable[0];
        var last = $focusable[$focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    /* ── Swipe down to close (grabber + header) ──────────────────────── */

    var touchStartY = 0;
    var touchDelta = 0;
    var $dragPanel = null;
    var dragging = false;

    $(document).on('touchstart', '.rmm-catsheet-grabber, .rmm-catsheet-head', function (e) {
        if (reducedMotion || !e.originalEvent.touches || !e.originalEvent.touches.length) {
            return;
        }
        touchStartY = e.originalEvent.touches[0].clientY;
        touchDelta = 0;
        dragging = true;
        $dragPanel = $(this).closest('.rmm-catsheet-panel');
        $dragPanel.css('transition', 'none');
    });

    $(document).on('touchmove', '.rmm-catsheet-grabber, .rmm-catsheet-head', function (e) {
        if (!dragging || !$dragPanel || !e.originalEvent.touches || !e.originalEvent.touches.length) {
            return;
        }

        touchDelta = e.originalEvent.touches[0].clientY - touchStartY;
        if (touchDelta > 0) {
            e.preventDefault();
            $dragPanel.css('transform', 'translateY(' + Math.min(touchDelta, 240) + 'px)');
        }
    });

    $(document).on('touchend touchcancel', '.rmm-catsheet-grabber, .rmm-catsheet-head', function () {
        if (!dragging || !$dragPanel) {
            return;
        }

        dragging = false;
        var shouldClose = touchDelta > 90;
        var $root = getRoot($dragPanel);

        $dragPanel.css({ transition: '', transform: '' });
        $dragPanel = null;

        if (shouldClose) {
            closeSheet($root);
        }
    });

})(window.jQuery);
