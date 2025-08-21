(function (wp) {
	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { PanelBody, Notice, Spinner } = wp.components;
	const { createElement: el, useState } = wp.element;
	const { useDispatch, select, subscribe } = wp.data;

    async function logToPhp(message, level = "INFO") {
    try {
        await fetch(W2P2_LOGGER.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "w2p2_log_message",
                nonce: W2P2_LOGGER.nonce,
                message,
                level
            }),
            credentials: "same-origin"
        });
    } catch (err) {
        console.error("Failed to log to PHP:", err);
    }
}

	if (!wp || !wp.data || !select) return;
	const expectedPT = (window.MGC_SETTINGS && MGC_SETTINGS.postType) || 'w2p2_import';
    // only allow UI to show on this special CPT
	const unsubscribe = subscribe(() => {
		const pt =
			(select('core/editor')?.getCurrentPostType?.()) ||
			(select('core/editor')?.getEditedPostAttribute?.('type'));
		const postId = select('core/editor')?.getCurrentPostId?.();

		if (!pt || !postId) return;           
		if (pt !== expectedPT) { unsubscribe(); return; }

		unsubscribe();

		function MammothSidebar() {
			const { insertBlocks } = useDispatch('core/block-editor');
			const [loading, setLoading] = useState(false);
			const [messages, setMessages] = useState([]);
			const [selected, setSelected] = useState(null);

			async function handleFile(e) {
				const file = e.target.files?.[0];
				if (!file) return;

                await logToPhp(`User selected file: ${file.name}`, "INFO");

				if (!file.name.toLowerCase().endsWith('.docx')) {
					window.alert(__('Please select a valid .docx file.', 'mgc'));
                    await logToPhp(`Please select a valid .docx file. ${file.name}`, "INFO");
					return;
				}

				setMessages([]);
				setSelected(file.name);
				setLoading(true);

				try {
					const arrayBuffer = await file.arrayBuffer();
					const result = await window.mammoth.convertToHtml({ arrayBuffer });
					const rawHtml = result.value || '';
					setMessages(result.messages || []);

					const res = await fetch(MGC_SETTINGS.ajaxUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							action: 'mgc_sanitize_html',
							nonce: MGC_SETTINGS.nonce,
							html: rawHtml,
						}),
						credentials: 'same-origin',
					});

					const data = await res.json();
					if (!data || !data.success) {
						throw new Error((data && data.data && data.data.message) || 'Sanitisation failed');
					}

					const safeHtml = data.data.html || '';
					const blocks = wp.blocks.rawHandler({ HTML: safeHtml });
					if (blocks && blocks.length) {
						insertBlocks(blocks);
					} else {
						window.alert(__('Nothing to insert — the file may be empty after sanitisation.', 'mgc'));
					}
				} catch (err) {
					console.error('Word conversion failed:', err);
					window.alert(__('Failed to convert or insert file. See console for details.', 'mgc'));
				} finally {
					setLoading(false);
					e.target.value = '';
				}
			}

			return el(
				wp.element.Fragment,
				null,
				el(PluginSidebarMoreMenuItem, { target: 'mgc-sidebar' }, 'Mammoth DOCX'),
				el(
					PluginSidebar,
					{ name: 'mgc-sidebar', title: __('Convert Word to WordPress', 'mgc') },
					el(
						PanelBody,
						{ title: __('Convert a Word .docx file', 'mgc'), initialOpen: true },
						el('p', null, __('Select a .docx file to convert to a Gutenberg WordPress post. Content is sanitised with wp_kses_post() before insertion, no files are stored on the server', 'mgc')),
						el('input', {
							type: 'file',
							accept: '.docx',
							onChange: handleFile,
							'aria-label': __('Select .docx file', 'mgc'),
							disabled: loading,
						}),
						selected && el('p', { style: { marginTop: 8 } }, __('Selected:', 'mgc'), ' ', el('strong', null, selected)),
						loading && el('p', { style: { marginTop: 8 } }, el(Spinner, null), ' ', __('Converting…', 'mgc'))
					),
					el(
						PanelBody,
						{ title: __('Messages', 'mgc'), initialOpen: false },
						messages.length === 0
							? el('p', null, __('No messages yet.', 'mgc'))
							: messages.map((m, i) =>
									el(
										Notice,
										{
											key: i,
											status: m.type === 'warning' ? 'warning' : 'info',
											isDismissible: false,
											style: { marginBottom: 8 },
										},
										m.message || JSON.stringify(m)
									)
							  )
					)
				)
			);
		}

		registerPlugin('mgc-sidebar', { render: MammothSidebar });
	}); 
})(window.wp);
