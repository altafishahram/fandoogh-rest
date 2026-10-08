(function (blocks, element, components, editor, i18n) {
    'use strict';
    const el = element.createElement;
    const t = (message) => i18n.__(message, 'admincafe');
    blocks.registerBlockType('admincafe/menu', {
        title: t('AdminCafe menu'), icon: 'food', category: 'widgets',
        attributes: { component: { type: 'string', default: 'menu' }, category: { type: 'number', default: 0 }, mode: { type: 'string', default: 'auto' } },
        edit: function (props) {
            return el('div', editor.useBlockProps(),
                el(editor.InspectorControls, {}, el(components.PanelBody, { title: t('Restaurant menu') },
                    el(components.SelectControl, { label: t('Component'), value: props.attributes.component, options: [
                        { label: t('Complete menu'), value: 'menu' }, { label: t('Category navigation'), value: 'categories' },
                        { label: t('Product cards'), value: 'products' }, { label: t('Order basket'), value: 'cart' }
                    ], onChange: value => props.setAttributes({ component: value }) }),
                    el(components.TextControl, { label: t('Category ID (0 for all)'), type: 'number', min: 0, value: props.attributes.category, onChange: value => props.setAttributes({ category: Math.max(0, Number(value) || 0) }) }),
                    el(components.SelectControl, { label: t('Ordering'), value: props.attributes.mode, options: [
                        { label: t('Follow restaurant settings'), value: 'auto' }, { label: t('View menu only'), value: 'menu' }
                    ], onChange: value => props.setAttributes({ mode: value }) })
                )),
                el('div', { style: { padding: '28px', border: '1px solid #e9ddd0', borderRadius: '16px', background: '#faf8f5' } },
                    el('strong', {}, t('AdminCafe menu')),
                    el('p', {}, t('The live restaurant menu appears on the published page. Products and ordering follow restaurant settings.')),
                    el('code', {}, '[admincafe_' + props.attributes.component + ']')
                )
            );
        },
        save: function () { return null; }
    });
})(window.wp.blocks, window.wp.element, window.wp.components, window.wp.blockEditor, window.wp.i18n);
